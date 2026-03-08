#!/usr/bin/env bash
set -euo pipefail

# Resolve the directory where this script lives
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILLS_SOURCE="$SCRIPT_DIR/skills"
SKILLS_TARGET="${HOME}/.claude/skills"

# Deprecation banner
echo "============================================================"
echo "  DEPRECATED: install.sh is no longer the recommended"
echo "  installation method for Drupal Skills."
echo ""
echo "  Use Claude Code's plugin system instead:"
echo ""
echo "    claude --plugin-dir /path/to/drupal-skills"
echo ""
echo "  To uninstall previously installed skills:"
echo ""
echo "    ./install.sh --uninstall"
echo ""
echo "  See README.md for full migration instructions."
echo "============================================================"
echo ""

# Validate source directory exists
if [ ! -d "$SKILLS_SOURCE" ]; then
  echo "Error: skills/ directory not found at $SKILLS_SOURCE"
  exit 1
fi

# Parse arguments
SYMLINK_MODE=false
UNINSTALL_MODE=false
for arg in "$@"; do
  case "$arg" in
    --symlink)
      SYMLINK_MODE=true
      ;;
    --uninstall)
      UNINSTALL_MODE=true
      ;;
    -h|--help)
      echo "Usage: ./install.sh [--symlink] [--uninstall]"
      echo ""
      echo "DEPRECATED: Use 'claude --plugin-dir /path/to/drupal-skills' instead."
      echo ""
      echo "Options:"
      echo "  --symlink    Create symlinks instead of copies (auto-updates, but breaks if repo moves)"
      echo "  --uninstall  Remove all previously installed drupal-* skills from ~/.claude/skills/"
      echo "  -h, --help   Show this help message"
      exit 0
      ;;
    *)
      echo "Unknown option: $arg"
      echo "Usage: ./install.sh [--symlink] [--uninstall]"
      exit 1
      ;;
  esac
done

# Handle --uninstall
if [ "$UNINSTALL_MODE" = true ]; then
  COUNT=0
  for skill in "$SKILLS_TARGET"/drupal-*/; do
    [ -d "$skill" ] || [ -L "$skill" ] || continue
    skill_name="$(basename "$skill")"
    echo "Removing: $skill_name"
    rm -rf "$skill"
    COUNT=$((COUNT + 1))
  done
  echo ""
  echo "Removed $COUNT Drupal skill(s) from $SKILLS_TARGET"
  echo "Use 'claude --plugin-dir /path/to/drupal-skills' for plugin-based installation."
  exit 0
fi

# Create target directory
mkdir -p "$SKILLS_TARGET"

# Install each drupal skill
COUNT=0
for skill_dir in "$SKILLS_SOURCE"/drupal-*/; do
  [ -d "$skill_dir" ] || continue
  skill_name="$(basename "$skill_dir")"
  target="$SKILLS_TARGET/$skill_name"

  if [ -e "$target" ] || [ -L "$target" ]; then
    echo "Updating: $skill_name"
    rm -rf "$target"
  else
    echo "Installing: $skill_name"
  fi

  if [ "$SYMLINK_MODE" = true ]; then
    # Use absolute path for symlink
    ln -s "$(cd "$skill_dir" && pwd)" "$target"
  else
    cp -r "$skill_dir" "$target"
  fi

  COUNT=$((COUNT + 1))
done

echo ""
echo "Installed $COUNT Drupal skills to $SKILLS_TARGET"
if [ "$SYMLINK_MODE" = true ]; then
  echo "Mode: symlink (skills update automatically when you pull changes)"
else
  echo "Mode: copy (re-run install.sh to get updates)"
fi
echo "Skills will be available in your next Claude Code session."

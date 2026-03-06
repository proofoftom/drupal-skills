#!/usr/bin/env bash
#
# E2E assertion helper for eval grading phase.
# Uses agent-browser to check page content against expectations.
#
# Usage: e2e-assert.sh <ddev-project-name> <url-path> <assertion-type> [expected-value]
#
# Assertion types:
#   page-contains <text>      - Check if page text contains expected text
#   status-ok                 - Verify page loads without error (not 404/403)
#   status-forbidden          - Verify page returns 403 Access Denied
#   element-exists <selector> - Check CSS selector matches an element
#   form-has-field <label>    - Verify a form field with given label exists
#
# Exit codes: 0 = PASS, 1 = FAIL
#
set -euo pipefail

DDEV_PROJECT="${1:?Usage: e2e-assert.sh <ddev-project-name> <url-path> <assertion-type> [expected-value]}"
URL_PATH="${2:?Usage: e2e-assert.sh <ddev-project-name> <url-path> <assertion-type> [expected-value]}"
ASSERTION_TYPE="${3:?Usage: e2e-assert.sh <ddev-project-name> <url-path> <assertion-type> [expected-value]}"
EXPECTED_VALUE="${4:-}"

BASE_URL="https://os-kg-${DDEV_PROJECT}.ddev.site"
FULL_URL="${BASE_URL}${URL_PATH}"

# Use a unique session name to avoid collisions with parallel evals
SESSION_NAME="eval-${DDEV_PROJECT}-$$"

# Ensure agent-browser is closed on exit to prevent leaked browser processes
cleanup() {
  agent-browser --session "$SESSION_NAME" close 2>/dev/null || true
}
trap cleanup EXIT

# Helper: open the URL and wait for it to load
open_url() {
  agent-browser --session "$SESSION_NAME" --ignore-https-errors open "$FULL_URL" 2>/dev/null
}

# Helper: get page snapshot text (accessibility tree)
get_page_text() {
  agent-browser --session "$SESSION_NAME" snapshot 2>/dev/null
}

# Helper: get page title
get_title() {
  agent-browser --session "$SESSION_NAME" get title 2>/dev/null
}

case "$ASSERTION_TYPE" in
  page-contains)
    if [ -z "$EXPECTED_VALUE" ]; then
      echo "FAIL: page-contains requires an expected value" >&2
      exit 1
    fi
    open_url
    PAGE_TEXT=$(get_page_text)
    if echo "$PAGE_TEXT" | grep -qiF "$EXPECTED_VALUE"; then
      echo "PASS: page-contains '$EXPECTED_VALUE' at $URL_PATH"
      exit 0
    else
      echo "FAIL: page-contains '$EXPECTED_VALUE' at $URL_PATH -- text not found"
      exit 1
    fi
    ;;

  status-ok)
    open_url
    TITLE=$(get_title)
    PAGE_TEXT=$(get_page_text)
    # Check for common error indicators
    if echo "$TITLE" | grep -qi "Page not found"; then
      echo "FAIL: status-ok at $URL_PATH -- got 'Page not found'"
      exit 1
    fi
    if echo "$PAGE_TEXT" | grep -qiF "The requested page could not be found"; then
      echo "FAIL: status-ok at $URL_PATH -- page not found message detected"
      exit 1
    fi
    if echo "$TITLE" | grep -qi "Access denied"; then
      echo "FAIL: status-ok at $URL_PATH -- got 'Access denied'"
      exit 1
    fi
    echo "PASS: status-ok at $URL_PATH"
    exit 0
    ;;

  status-forbidden)
    open_url
    TITLE=$(get_title)
    PAGE_TEXT=$(get_page_text)
    if echo "$PAGE_TEXT" | grep -qiF "Access denied"; then
      echo "PASS: status-forbidden at $URL_PATH"
      exit 0
    fi
    if echo "$TITLE" | grep -qi "403"; then
      echo "PASS: status-forbidden at $URL_PATH"
      exit 0
    fi
    echo "FAIL: status-forbidden at $URL_PATH -- expected 403/Access denied"
    exit 1
    ;;

  element-exists)
    if [ -z "$EXPECTED_VALUE" ]; then
      echo "FAIL: element-exists requires a CSS selector" >&2
      exit 1
    fi
    open_url
    # Use eval to check for element existence via querySelector
    RESULT=$(agent-browser --session "$SESSION_NAME" eval "document.querySelector('${EXPECTED_VALUE}') !== null" 2>/dev/null)
    if echo "$RESULT" | grep -q "true"; then
      echo "PASS: element-exists '$EXPECTED_VALUE' at $URL_PATH"
      exit 0
    else
      echo "FAIL: element-exists '$EXPECTED_VALUE' at $URL_PATH -- element not found"
      exit 1
    fi
    ;;

  form-has-field)
    if [ -z "$EXPECTED_VALUE" ]; then
      echo "FAIL: form-has-field requires a field label" >&2
      exit 1
    fi
    open_url
    # Use agent-browser find label to locate the form field
    RESULT=$(agent-browser --session "$SESSION_NAME" find label "$EXPECTED_VALUE" 2>&1) || true
    if echo "$RESULT" | grep -qi "error\|not found\|no .* found\|could not find"; then
      echo "FAIL: form-has-field '$EXPECTED_VALUE' at $URL_PATH -- field not found"
      exit 1
    fi
    # If find didn't error, the label was found
    echo "PASS: form-has-field '$EXPECTED_VALUE' at $URL_PATH"
    exit 0
    ;;

  *)
    echo "FAIL: Unknown assertion type '$ASSERTION_TYPE'" >&2
    echo "Valid types: page-contains, status-ok, status-forbidden, element-exists, form-has-field" >&2
    exit 1
    ;;
esac

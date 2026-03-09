#!/bin/bash

# Configuration
TEST_DIR="tests"
PHPUNIT="./vendor/bin/phpunit"
EXCLUDE="tests/StoreStatementApiTest.php"

# Check if phpunit exists
if [ ! -f "$PHPUNIT" ]; then
    echo "Error: phpunit not found at $PHPUNIT"
    exit 1
fi

# Get all test files, excluding the one specified
TEST_FILES=$(find "$TEST_DIR" -name "*Test.php" -not -path "*/vendor/*" -not -path "$EXCLUDE")

TOTAL_TESTS=$(echo "$TEST_FILES" | wc -l | xargs)
PASSED=0
FAILED=0
FAILED_FILES=()

echo "Starting sequential test run ($TOTAL_TESTS files, excluding $EXCLUDE)..."
echo "------------------------------------------------"

COUNTER=0
for FILE in $TEST_FILES; do
    COUNTER=$((COUNTER + 1))
    echo "[$COUNTER/$TOTAL_TESTS] Running $FILE..."
    
    # Run the test
    $PHPUNIT "$FILE"
    
    if [ $? -eq 0 ]; then
        PASSED=$((PASSED + 1))
    else
        FAILED=$((FAILED + 1))
        FAILED_FILES+=("$FILE")
    fi
    echo "------------------------------------------------"
done

# Summary
echo "Test Run Summary:"
echo "Total Files: $TOTAL_TESTS"
echo "Passed:      $PASSED"
echo "Failed:      $FAILED"

if [ $FAILED -gt 0 ]; then
    echo ""
    echo "Failed Test Files:"
    for FILE in "${FAILED_FILES[@]}"; do
        echo "  - $FILE"
    done
    exit 1
else
    echo ""
    echo "All non-excluded tests passed!"
    exit 0
fi

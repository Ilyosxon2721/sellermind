#!/bin/bash

# SellerMind Smoke Tests
# Quick validation of critical API endpoints before deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="${BASE_URL:-http://localhost:8000}"
API_TOKEN="${API_TOKEN:-}"

echo "========================================"
echo "  SellerMind Smoke Tests"
echo "========================================"
echo "Base URL: $BASE_URL"
echo ""

# Test counter
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0

# Helper function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_status=$3
    local description=$4
    local data=$5

    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    echo -n "Testing: $description... "

    if [ -n "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $API_TOKEN" \
            -d "$data" \
            "$BASE_URL$endpoint")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method \
            -H "Authorization: Bearer $API_TOKEN" \
            "$BASE_URL$endpoint")
    fi

    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)

    if [ "$http_code" == "$expected_status" ]; then
        echo -e "${GREEN}✓ PASSED${NC} (HTTP $http_code)"
        PASSED_TESTS=$((PASSED_TESTS + 1))
        return 0
    else
        echo -e "${RED}✗ FAILED${NC} (Expected: $expected_status, Got: $http_code)"
        echo "Response: $body"
        FAILED_TESTS=$((FAILED_TESTS + 1))
        return 1
    fi
}

# Health Check
echo "=== Health Checks ==="
test_endpoint "GET" "/" "200" "Homepage loads"
test_endpoint "GET" "/api/health" "200" "API health check" || true

echo ""
echo "=== Authentication ==="
test_endpoint "GET" "/login" "200" "Login page loads"
test_endpoint "GET" "/register" "200" "Register page loads"

echo ""
echo "=== UI Pages ==="
test_endpoint "GET" "/dashboard" "200" "Dashboard page" || true
test_endpoint "GET" "/promotions" "200" "Promotions page" || true
test_endpoint "GET" "/analytics" "200" "Analytics page" || true
test_endpoint "GET" "/reviews" "200" "Reviews page" || true

if [ -n "$API_TOKEN" ]; then
    echo ""
    echo "=== Promotions API ==="
    test_endpoint "GET" "/api/promotions" "200" "List promotions"
    test_endpoint "GET" "/api/promotions/statistics" "200" "Promotion statistics" || true

    echo ""
    echo "=== Analytics API ==="
    test_endpoint "GET" "/api/analytics/dashboard" "200" "Analytics dashboard" || true
    test_endpoint "GET" "/api/analytics/overview?period=30days" "200" "Analytics overview" || true

    echo ""
    echo "=== Reviews API ==="
    test_endpoint "GET" "/api/reviews?status=pending" "200" "List reviews" || true
    test_endpoint "GET" "/api/reviews/statistics" "200" "Review statistics" || true
    test_endpoint "GET" "/api/reviews/templates" "200" "Review templates" || true
else
    echo ""
    echo -e "${YELLOW}⚠ API_TOKEN not set, skipping authenticated tests${NC}"
    echo "To test authenticated endpoints, run:"
    echo "  export API_TOKEN=your_token_here"
    echo "  ./tests/smoke-tests.sh"
fi

echo ""
echo "=== Laravel Artisan Commands ==="
echo -n "Testing: Artisan schedule:list... "
if php artisan schedule:list > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ FAILED${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo -n "Testing: Artisan queue:failed... "
if php artisan queue:failed > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ FAILED${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "=== Database Connectivity ==="
echo -n "Testing: Database connection... "
if php artisan db:show > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ FAILED${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "=== Cache & Session ==="
echo -n "Testing: Cache driver... "
if php artisan cache:clear > /dev/null 2>&1; then
    echo -e "${GREEN}✓ PASSED${NC}"
    PASSED_TESTS=$((PASSED_TESTS + 1))
else
    echo -e "${RED}✗ FAILED${NC}"
    FAILED_TESTS=$((FAILED_TESTS + 1))
fi
TOTAL_TESTS=$((TOTAL_TESTS + 1))

echo ""
echo "========================================"
echo "  Test Results"
echo "========================================"
echo "Total Tests:  $TOTAL_TESTS"
echo -e "Passed:       ${GREEN}$PASSED_TESTS${NC}"
if [ $FAILED_TESTS -gt 0 ]; then
    echo -e "Failed:       ${RED}$FAILED_TESTS${NC}"
else
    echo "Failed:       $FAILED_TESTS"
fi
echo ""

# Exit with error if any test failed
if [ $FAILED_TESTS -gt 0 ]; then
    echo -e "${RED}❌ Some tests failed!${NC}"
    exit 1
else
    echo -e "${GREEN}✅ All tests passed!${NC}"
    exit 0
fi

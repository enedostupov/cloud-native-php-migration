#!/bin/bash
set -e

echo "Testing Gateway with Authentication"
echo "==================================="
echo ""

BASE_URL="${BASE_URL:-http://localhost:3000}"

echo "1. Health check endpoint"
curl -s "$BASE_URL/health" | jq .
echo ""

echo "2. Try API without token"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/items")
if [ "$HTTP_CODE" = "401" ]; then
    echo "Got 401 as expected"
    curl -s "$BASE_URL/api/items" | jq .
else
    echo "Failed: expected 401, got $HTTP_CODE"
    exit 1
fi
echo ""

echo "3. Login"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}')

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.token')

if [ "$TOKEN" = "null" ] || [ -z "$TOKEN" ]; then
    echo "Failed to get token"
    echo "$LOGIN_RESPONSE" | jq .
    exit 1
fi

echo "Token: ${TOKEN:0:50}..."
echo ""

echo "4. GET with token"
curl -s "$BASE_URL/api/items" \
  -H "Authorization: Bearer $TOKEN" | jq .
echo ""

echo "5. POST with token"
CREATED=$(curl -s -X POST "$BASE_URL/api/items" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Book","author":"Test Author"}')
echo "$CREATED" | jq .

ITEM_ID=$(echo "$CREATED" | jq -r '.id')
echo ""

echo "6. GET item $ITEM_ID"
curl -s "$BASE_URL/api/items/$ITEM_ID" \
  -H "Authorization: Bearer $TOKEN" | jq .
echo ""

echo "7. PUT item $ITEM_ID"
curl -s -X PUT "$BASE_URL/api/items/$ITEM_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Updated","author":"Updated"}' | jq .
echo ""

echo "8. DELETE item $ITEM_ID"
curl -s -X DELETE "$BASE_URL/api/items/$ITEM_ID" \
  -H "Authorization: Bearer $TOKEN" | jq .
echo ""

echo "9. Invalid token"
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" \
  -H "Authorization: Bearer bad-token" \
  "$BASE_URL/api/items")

if [ "$HTTP_CODE" = "401" ]; then
    echo "Rejected invalid token"
else
    echo "Failed: expected 401, got $HTTP_CODE"
fi
echo ""

echo "10. Wrong credentials"
WRONG=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"username":"bad","password":"bad"}')

if echo "$WRONG" | jq -e '.error == "invalid_credentials"' > /dev/null; then
    echo "Rejected bad credentials"
else
    echo "Failed: should reject bad credentials"
fi
echo ""

echo "11. Rate limiting (105 requests)"
SUCCESS=0
LIMITED=0

for i in {1..105}; do
    CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/health")
    if [ "$CODE" = "200" ]; then
        ((SUCCESS++)) || true
    elif [ "$CODE" = "429" ]; then
        ((LIMITED++)) || true
        if [ "$LIMITED" = "1" ]; then
            echo "Hit rate limit at request $i"
            curl -s "$BASE_URL/health" | jq .
        fi
    fi
    
    if [ $((i % 25)) -eq 0 ]; then
        echo "Progress: $i/105"
    fi
done

echo "Success: $SUCCESS, Limited: $LIMITED"
if [ "$LIMITED" -gt 0 ]; then
    echo "Rate limiter working"
else
    echo "Rate limiter not triggered"
fi
echo ""

echo "==================================="
echo "Tests complete"
echo ""
echo "Credentials: admin/admin"

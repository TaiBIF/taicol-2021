#!/usr/bin/env bash
# 升級期間的快速煙霧測試
# 用法：BASE=http://localhost:8000 ./tests/manual/smoke.sh
BASE="${BASE:-http://localhost:8000}"
fail=0
for p in / /zh-tw /api/countries /api/nomenclatures /api/ranks \
         "/api/search?keyword=Pica" /api/references "/api/taxon-names?keyword=Pica" /api/simple-taxon-names; do
  code=$(curl -s -o /dev/null -w '%{http_code}' "$BASE$p")
  if [[ "$code" == 200 ]]; then echo "OK   $code $p"; else echo "FAIL $code $p"; fail=1; fi
done
exit $fail
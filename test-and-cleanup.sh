#!/bin/bash

# Wrapper script untuk menjalankan test dan cleanup
# Cleanup akan tetap jalan meskipun test gagal

# Simpan arguments yang diberikan
ARGS="$@"

# Jalankan test dengan arguments
if [ -z "$ARGS" ]; then
    # Tanpa arguments - run semua test
    php artisan test
else
    # Dengan arguments
    php artisan test $ARGS
fi

# Simpan exit code dari test
TEST_EXIT_CODE=$?

# Jalankan cleanup (selalu jalan, ignore errors)
./cleanup-test-db.sh 2>/dev/null || true

# Return exit code dari test (bukan cleanup)
exit $TEST_EXIT_CODE

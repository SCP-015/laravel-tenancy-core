#!/bin/bash

# Script untuk membersihkan file database testing dan folder storage tenant
# Usage: ./cleanup-test-db.sh

echo "🧹 Cleaning up testing databases and storage..."

# ===== CLEANUP DATABASE =====
echo ""
echo "📦 Cleaning testing databases..."

# Hitung jumlah file sebelum dihapus
FILE_COUNT=$(find database/testing/ -name "nusahire_*" -type f | wc -l | tr -d ' ')

if [ "$FILE_COUNT" -eq 0 ]; then
    echo "✅ No testing database files to clean"
else
    echo "📁 Found $FILE_COUNT database file(s)"
    
    # Hapus file database testing
    find database/testing/ -name "nusahire_*" -type f -delete
    
    # Verifikasi
    REMAINING=$(find database/testing/ -name "nusahire_*" -type f | wc -l | tr -d ' ')
    
    if [ "$REMAINING" -eq 0 ]; then
        echo "✅ Successfully cleaned $FILE_COUNT database file(s)"
    else
        echo "⚠️  Warning: $REMAINING file(s) still remaining"
    fi
fi

# ===== CLEANUP TESTING STORAGE =====
echo ""
echo "📁 Cleaning testing storage folder (storage/testing/)..."

# Hitung jumlah folder storage testing sebelum dihapus
STORAGE_COUNT=$(find storage/testing -maxdepth 1 -type d -name "nusahire_*" 2>/dev/null | wc -l | tr -d ' ')

if [ "$STORAGE_COUNT" -eq 0 ]; then
    echo "✅ No testing storage folders to clean"
else
    echo "📁 Found $STORAGE_COUNT storage folder(s) in storage/testing/"
    
    # Hapus semua folder storage tenant di folder testing
    find storage/testing -maxdepth 1 -type d -name "nusahire_*" -exec rm -rf {} + 2>/dev/null
    
    # Verifikasi
    REMAINING_STORAGE=$(find storage/testing -maxdepth 1 -type d -name "nusahire_*" 2>/dev/null | wc -l | tr -d ' ')
    
    if [ "$REMAINING_STORAGE" -eq 0 ]; then
        echo "✅ Successfully cleaned $STORAGE_COUNT storage folder(s)"
        echo "📂 Folder storage/testing/ is now clean"
    else
        echo "⚠️  Warning: $REMAINING_STORAGE folder(s) still remaining"
        exit 1
    fi
fi

echo ""
echo "📂 Cleanup completed!"

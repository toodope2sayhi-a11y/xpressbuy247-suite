name: Build and Package XpressBuy247 (Core 4 – v2)

on:
  push:
    branches: [ "main" ]
  workflow_dispatch:

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
      - name: Checkout repository
        uses: actions/checkout@v4

      - name: Set up PHP with ZIP extension
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
          extensions: zip

      - name: Confirm ZIP extension enabled
        run: php -m | grep zip || (echo "ZIP extension missing" && exit 1)

      - name: Run Plugin Builder
        run: php builder/build.php

      - name: Verify build directory
        run: |
          echo "Contents of build folder:"
          ls -lah build || echo "No build directory found"

      - name: List all ZIPs found
        run: find . -name "*.zip" -type f -exec ls -lh {} \;

      - name: Upload all built plugins
        uses: actions/upload-artifact@v4
        with:
          name: xpressbuy247-latest-suite
          path: build/*.zip

#!/bin/bash

WATCH_DIR="/root/Junior_Web/uploads"
YARA_RULE="/opt/gen_webshells.yar"
QUARANTINE_DIR="/opt/quarantine"

mkdir -p "$QUARANTINE_DIR"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# 檢查 inotify-tools 是否存在
if ! command -v inotifywait >/dev/null; then
    log "[!] 請先安裝 inotify-tools"
    log "    apt install inotify-tools"
    exit 1
fi

log "[*] 開始監控 $WATCH_DIR"

inotifywait -m -e close_write --format '%f' "$WATCH_DIR" | while read FILE
do
    FILE_PATH="$WATCH_DIR/$FILE"
    log "[+] 偵測到新檔案: $FILE_PATH"

    # 執行 YARA 掃描並存到變數
    MATCHES=$(yara "$YARA_RULE" "$FILE_PATH")

    if [[ -n "$MATCHES" ]]; then
        log "[!] 命中規則: $MATCHES"

        # 取得檔案 sha256 hash
        HASH=$(sha256sum "$FILE_PATH" | awk '{print $1}')

        NEW_NAME="${HASH}"

        # 移動並避免覆蓋
        mv "$FILE_PATH" "$QUARANTINE_DIR/$NEW_NAME"

        if [[ $? -eq 0 ]]; then
            log "[+] 已移動到隔離: $QUARANTINE_DIR/$NEW_NAME"
        else
            log "[!] 移動失敗，可能檔案已存在: $QUARANTINE_DIR/$NEW_NAME"
        fi
    else
        log "[-] 未命中: $FILE_PATH"
    fi
done
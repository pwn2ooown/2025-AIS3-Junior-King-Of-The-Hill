# 2025 AIS3 Junior 煉蠱大賽

Try to be the king of the hill!!!

## Intended path

### Get shell

- Use LFI to RCE in `index.php`
- Forge session in `filebrowser.php` (PHP easy deserialization) and upload `.htaccess` to bypass
- 5 byte command to FULL RCE by 🍊 (<https://github.com/orangetw/My-CTF-Web-Challenges/tree/master/hitcon-ctf-2017/babyfirst-revenge>)

### Get root

- find SUID (<https://gtfobins.github.io/gtfobins/find>)
- SUID custom systemd-info for path hijacking
- Abuse sudo less gtfobins (<https://gtfobins.github.io/gtfobins/less/>)

## Persistence

- Persistent reverse shell
- Kill other pid
- php.ini / apache config for backdoors, patches (Can restart the service)
- ...There's way more for you to discover!

## Remark

Since upload is too EZ I added an "anti-virus" to kill webshells in the middle of the game.

...Actually it's just yara scan, rule: <https://github.com/Neo23x0/signature-base/blob/4d54d52962aba625443d8562e59ce49cfd6984ae/yara/gen_webshells.yar>

...Which is pretty easy to bypass the rules, I think only using static string rules to detect webshells is just not enough

See [scan.sh](scan.sh) for more details.

## Environment

`docker compose up -d` should be fine

uploads, sandbox folder is mounted in so make it writeable for all users

systemd-info needs to be suid

## LICENSE

MIT

## Disclaimer

This environment is intentionally configured with vulnerabilities for educational or testing purposes. DO NOT DEPLOY IN PRODUCTION. All content within this environment is AI-generated and should not be associated with any real individuals or organizations. The author assumes no responsibility or liability for any consequences arising from the use of this environment. This software is provided as is, without any warranty of any kind.

## Epilogue

Hope you all have fun playing with this challenge machine and discover lots of cool things!

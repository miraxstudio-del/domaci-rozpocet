# Tok dat a ověření lokálního provozu

Tento dokument popisuje chování aktuální oficiální verze projektu Domácí
rozpočet. Slouží uživatelům, kteří si chtějí před spuštěním ověřit, co se děje
s jejich údaji.

## Žádný automatický odchod uživatelských dat

Aplikace neobsahuje účet, přihlášení k online službě, cloudové rozhraní,
telemetrii, analytiku, reklamu ani odesílání finančních dat autorovi. Neexistuje
žádný server Mirax Studio, na který by aplikace automaticky posílala databázi,
doklady, zálohy, exporty nebo informace o používání.

Při standardním spuštění `START.bat` používá vestavěný PHP server pouze na
adrese `127.0.0.1`. Tato adresa označuje vlastní počítač uživatele; server není
vystaven internetu ani dalším zařízením v síti.

## Kde data zůstávají

| Typ dat | Lokální umístění |
| --- | --- |
| Rozpočet a nastavení | `data/rozpocet.sqlite` |
| Doklady | `uploads/` |
| Zálohy | `data/backups/` |
| Exporty | `exporty/` |

Tyto složky jsou v `.gitignore`, takže skutečná uživatelská data se běžně
neodesílají ani při práci se zdrojovým kódem přes Git.

## Co může uživatel udělat vědomě

Uživatel se může sám rozhodnout data exportovat, uložit zálohu do cloudu nebo
otevřít odkaz na web Mirax Studio. Taková akce není automatickým přenosem dat
aplikací. Po kliknutí na externí odkaz se případné zpracování řídí pravidly
navštíveného webu.

## Jak to ověřit v kódu

- [`START.bat`](START.bat) spouští server s parametrem `-S 127.0.0.1`.
- [`app/src/config.php`](app/src/config.php) definuje všechny cesty pro data
  uvnitř složky aplikace.
- JavaScript používá místní úložiště pro volbu vzhledu a rozvržení; neobsahuje
  analytické nebo telemetrické endpointy.
- Zdroje projektu jsou veřejné a lze je nezávisle zkontrolovat před spuštěním.

Pokud budoucí verze přidá vzdálenou službu nebo automatický přenos dat, bude
tento dokument i [zásady soukromí](PRIVACY.md) před vydáním aktualizován.

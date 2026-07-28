# Soukromí

## Stručně

Domácí rozpočet je lokální aplikace. Při standardním spuštění pomocí
`START.bat` běží pouze na adrese `127.0.0.1`, tedy na počítači uživatele.
Mirax Studio nemá přístup k finančním záznamům, dokladům, zálohám ani exportům
vytvořeným v aplikaci.

## Jaká data aplikace ukládá

Uživatel zadává vlastní údaje o domácím rozpočtu a případně nahrává doklady.
Tyto údaje se ukládají pouze do složek v lokální kopii aplikace:

- `data/` — databáze, nastavení, relace a zálohy,
- `uploads/` — přiložené doklady,
- `exporty/` — uživatelem vytvořené exporty.

Aplikace tyto údaje automaticky neodesílá Mirax Studio ani jiným třetím
stranám. Neobsahuje uživatelské účty, registraci, cloudové úložiště,
telemetrii, analytiku ani reklamní síť.

## Technické údaje v prohlížeči

Pro lokální fungování aplikace může prohlížeč ukládat technicky nezbytné údaje:

- lokální relaci PHP pro fungování v rámci běhu aplikace,
- volbu světlého/tmavého vzhledu a rozvržení rozhraní v místním úložišti
  prohlížeče.

Tyto údaje slouží jen k fungování lokální aplikace a nejsou používány pro
sledování uživatelů ani odesílány autorovi.

## Odkazy a GitHub

Kliknutí na dobrovolný odkaz na web Mirax Studio otevře samostatný web; jeho
případné zpracování údajů se řídí zásadami soukromí tohoto webu. Stejně tak se
při návštěvě veřejného repozitáře uplatní podmínky a zásady soukromí GitHubu.
Tyto služby nejsou součástí lokálního běhu aplikace.

## Role a odpovědnost za data

Mirax Studio při běžném používání této lokální aplikace žádná uživatelská data
nepřijímá ani je nezpracovává. Uživatel rozhoduje o tom, jaká data do své
lokální kopie vloží, kde ji uloží a zda ji s někým sdílí. Pokud aplikaci
použije organizace nebo podnikatel, musí si vlastní povinnosti při práci s
osobními údaji posoudit podle svého konkrétního použití.

## Změny

Pokud by budoucí verze přidala účet, cloud, analytiku, vzdálenou podporu nebo
jiný přenos dat, bude tento dokument předem aktualizován.

================================================================
 DOMÁCÍ ROZPOČET - návod k použití
================================================================

Domácí rozpočet je jednoduchá lokální aplikace pro evidenci
příjmů a výdajů domácnosti. Běží celá na vašem počítači,
nepotřebuje internet ani žádnou instalaci a všechna data
zůstávají pouze ve složce aplikace.


----------------------------------------------------------------
1) JAK APLIKACI SPUSTIT
----------------------------------------------------------------

Dvakrát klikněte na soubor:

    START.bat

Otevře se krátce černé okno (spouští se v něm lokální server) a
během chvilky se aplikace sama otevře ve výchozím internetovém
prohlížeči (např. Edge nebo Chrome) na adrese podobné:

    http://127.0.0.1:8090/

Server běží pouze na vašem počítači (adresa 127.0.0.1 = "tento
počítač") - není dostupný z internetu ani z jiných zařízení ve
vaší síti.

Pokud se prohlížeč neotevře sám, otevřete si ho ručně a napište
do adresního řádku výše uvedenou adresu (přesné číslo portu
uvidíte v okně, které se po spuštění na chvíli objeví).

TIP: Pokud si nejste jistí, jestli aplikace už neběží, klidně
znovu spusťte START.bat - pokud server už běží, pouze se otevře
prohlížeč, nic se nezdvojí.


----------------------------------------------------------------
2) JAK APLIKACI BEZPEČNĚ UKONČIT
----------------------------------------------------------------

Dvakrát klikněte na soubor:

    STOP.bat

Tím se lokální server řádně vypne. Zavření okna prohlížeče
aplikaci samo o sobě nevypíná - server běží dál na pozadí, dokud
nespustíte STOP.bat (nebo nevypnete počítač).

Vaše data (databáze, doklady) zůstávají po ukončení bezpečně
uložená ve složce aplikace.


----------------------------------------------------------------
3) KDE JE DATABÁZE
----------------------------------------------------------------

Veškerá evidovaná data (příjmy, výdaje, kategorie, pravidelné
platby, rozpočty, nastavení) jsou uložená v jediném souboru:

    data\rozpocet.sqlite

Tento soubor se vytvoří automaticky při prvním spuštění. Je to
běžný SQLite soubor - nepotřebujete k němu žádný databázový
server.

Nemažte ani neupravujte tento soubor ručně, pokud přesně nevíte,
co děláte - raději použijte zálohování (viz bod 5).


----------------------------------------------------------------
4) KDE JSOU ULOŽENÉ ÚČTENKY A DOKLADY
----------------------------------------------------------------

Nahrané přílohy (účtenky, faktury, smlouvy, ostatní dokumenty)
se ukládají do složky:

    uploads\uctenky\
    uploads\faktury\
    uploads\smlouvy\
    uploads\ostatni\

Každý soubor dostane při nahrání bezpečný jedinečný název, aby
se nikdy nepřepsal jiný soubor. Původní název souboru zůstává
zobrazen v aplikaci (v sekci "Doklady" a u dané položky).


----------------------------------------------------------------
5) JAK VYTVOŘIT ZÁLOHU
----------------------------------------------------------------

V aplikaci přejděte do sekce "Zálohy" v levém menu a klikněte na
tlačítko "Vytvořit zálohu nyní". Vytvoří se soubor .zip obsahující
kompletní databázi i všechny nahrané doklady, uložený do složky:

    data\backups\

Aplikace navíc automaticky vytváří jednu zálohu denně při svém
spuštění (lze vypnout v Nastavení). Staré automatické zálohy se
průběžně mažou (posledních 14 se ponechává), ruční zálohy zůstávají
uložené, dokud je sami neodstraníte.

Zálohu si také můžete stáhnout tlačítkem "Stáhnout" - hodí se to
například pro uložení kopie mimo počítač (USB disk, cloud apod.).


----------------------------------------------------------------
6) JAK APLIKACI PŘENÉST NA JINÝ POČÍTAČ
----------------------------------------------------------------

1. Ukončete aplikaci pomocí STOP.bat.
2. Zkopírujte (nebo zabalte do ZIPu) CELOU složku aplikace
   "Domácí rozpočet" i s podsložkami app, data, uploads, exporty.
3. Přeneste ji na cílový počítač (USB disk, sdílená složka,
   cloud...) a rozbalte na libovolné místo.
4. Na cílovém počítači spusťte START.bat.

Aplikace si po přenesení sama pozná svou novou polohu (nikde
není napevno zadaná cesta ke konkrétnímu počítači) a naběhne se
všemi daty, doklady i nastavením tak, jak jste je zanechali.

Cílový počítač nepotřebuje mít nainstalované PHP, XAMPP ani nic
podobného - aplikace si přináší svůj vlastní přenosný PHP běhový
program ve složce app\php.

Poznámka: pokud by na velmi starém nebo minimálně vybaveném
Windows počítači aplikace nešla spustit, může chybět tzv. Visual
C++ Redistributable (běžná součást Windows). V takovém případě
nainstalujte oficiální "Microsoft Visual C++ Redistributable
2015-2022 (x64)" a zkuste START.bat spustit znovu.


----------------------------------------------------------------
7) JAK OBNOVIT DATA ZE ZÁLOHY
----------------------------------------------------------------

V sekci "Zálohy":

  a) Obnovení z existující zálohy uložené v aplikaci
     - U příslušné zálohy klikněte na tlačítko "Obnovit".
     - Potvrďte dialogové okno (před obnovou se automaticky
       vytvoří bezpečnostní záloha aktuálního stavu, takže o nic
       nepřijdete ani při omylu).

  b) Obnovení ze souboru zálohy z jiného počítače
     - V sekci "Importovat zálohu z jiného počítače" vyberte
       soubor .zip vytvořený touto aplikací a klikněte na
       "Importovat a obnovit".

V obou případech se obnova z technických důvodů (aby nedošlo k
poškození souborů) dokončí až při dalším spuštění aplikace.
Aplikace vás na to upozorní - stačí pak spustit STOP.bat a znovu
START.bat a obnovená data se automaticky načtou.


----------------------------------------------------------------
DALŠÍ POZNÁMKY
----------------------------------------------------------------

- Aplikace nevyžaduje přihlašování ani registraci - je určená
  pro použití jednou osobou/domácností na jednom počítači.
- Žádná data neopouští váš počítač - aplikace nic neodesílá
  na internet.
- Export dat (CSV, Excel, tisk/PDF) najdete v sekci "Export".
  Soubory se zároveň ukládají do složky "exporty".
- V Nastavení si můžete volitelně načíst ukázková data pro
  vyzkoušení aplikace - kdykoliv je pak lze jedním tlačítkem
  zase odstranit. Při prvním spuštění je aplikace zcela prázdná.

Přejeme příjemné hospodaření!
================================================================

# 🏡 Domácí rozpočet

**Jednoduchá, česká a úplně zdarma aplikace pro správu rodinného rozpočtu.**
Běží celá lokálně na vašem počítači — bez instalace, bez internetu, bez registrace.

![Verze](https://img.shields.io/badge/verze-1.00-4f46e5)
![Licence](https://img.shields.io/badge/licence-MIT%20(open--source)-16a34a)
![Platforma](https://img.shields.io/badge/platforma-Windows-0ea5e9)

Vytvořilo [Mirax Studio](https://www.miraxstudio.cz) · více o vzniku aplikace v sekci [O programu](#-proč-tahle-aplikace-vznikla) níže.

---

## 🚀 Rychlý start (pro úplné laiky, žádná instalace)

Fakt to zvládne úplně každý:

1. **Stáhněte** si tento repozitář — nahoře na GitHubu klikněte na zelené tlačítko
   **`Code`** → **`Download ZIP`**.
2. **Rozbalte** stažený ZIP soubor kamkoliv na disk (např. na Plochu nebo do
   `Dokumenty`).
3. Přesuňte celou rozbalenou složku tam, kde ji chcete mít trvale uloženou
   (klidně na USB disk, do cloudu apod. — je to jedno).
4. Ve složce **dvakrát klikněte na `START.bat`**.
5. Aplikace se sama spustí a otevře v prohlížeči. Hotovo! 🎉

Nic dalšího se instalovat nemusí — aplikace si sebou nese vlastní přenosný
PHP server, takže funguje i na počítači, kde nikdy nic podobného nebylo.

**Všechno, co si v aplikaci vytvoříte a uložíte** (položky, kategorie, doklady,
nastavení), **zůstává jen a pouze na vašem počítači**, přímo ve složce
aplikace (`data/` a `uploads/`). Nic se nikam neposílá na internet.

Pro ukončení aplikace spusťte `STOP.bat`. Podrobný návod (kde jsou data, jak
zálohovat, jak aplikaci přenést na jiný počítač...) najdete v souboru
[`README.txt`](README.txt) přímo ve složce aplikace.

---

## ✨ Co aplikace umí

### 📊 Přehled a dashboard
- Souhrn aktuálního měsíce i **celého roku** jedním kliknutím — příjmy, výdaje,
  zůstatek, pravidelné vs. jednorázové výdaje, nezaplacené platby
- Porovnání s předchozím obdobím, největší výdaj, nejpoužívanější kategorie
- Upozornění na blížící se splatnosti

### 💰 Příjmy a výdaje
- Evidence libovolného počtu položek s částkou, datem, kategorií, způsobem
  platby, stavem (zaplaceno / čeká / po splatnosti / částečně / zrušeno)
- Podpora záporných/opravných částek, vrácení peněz, převodů mezi účty
- Kopírování položek, tvorba položky z pravidelné platby jedním klikem
- Podnikatelské vs. soukromé výdaje (volitelně)
- Poznámky, štítky, čísla dokladů

### 🏷️ Kategorie
- Desítky předpřipravených českých kategorií a podkategorií (potraviny,
  bydlení, doprava, zdraví, podnikání, volný čas...)
- Vlastní kategorie, ikony, barvy a měsíční limity

### 🔁 Pravidelné platby
- Nájem, energie, pojištění, předplatné a další opakované platby
- Týdenní/měsíční/čtvrtletní/roční frekvence, automatické vytváření položek
  v novém měsíci, upozornění před splatností

### 🎯 Rozpočty
- Celkový měsíční rozpočet i limity pro jednotlivé kategorie
- Plánované příjmy, minimální zůstatek, finanční rezerva
- Přehledné ukazatele čerpání

### 🧾 Doklady a přílohy
- Nahrávání účtenek, faktur, smluv (PDF, JPG, PNG, WEBP a další)
- Náhled, stažení, vyhledávání dokladů podle data, částky, kategorie

### 📈 Statistiky
- Vlastní přehledné grafy (bez nutnosti internetu) — výdaje podle kategorií,
  vývoj v čase, způsoby platby, poměr příjmů a výdajů a další
- Přepínání mezi pohledem za **měsíc** i za **celý rok**
- Kliknutím na graf zobrazíte konkrétní položky

### 🗓️ Měsíce
- Podrobný přehled a uzavírání jednotlivých měsíců (vratné), porovnání dvou
  měsíců, tisk / export přehledu

### ⬇️ Export a 💾 zálohy
- Export do CSV, Excelu (XLSX) i tisku/PDF, se správnou českou diakritikou
- Automatická denní záloha + ruční záloha jedním tlačítkem
- Bezpečná obnova ze zálohy (i ze zálohy přenesené z jiného počítače)

---

## 🔒 Soukromí a bezpečnost

- Lokální server naslouchá **pouze na `127.0.0.1`** — není dostupný z
  internetu ani z jiných zařízení ve vaší síti
- Žádná telemetrie, žádné reklamy, žádná registrace ani cloud
- Databáze je obyčejný SQLite soubor, který máte plně pod kontrolou

## 🛠️ Použité technologie

Čisté PHP 8.2 (bez frameworku) + SQLite, vlastní JS grafy nad `<canvas>`,
přenosný PHP běhový program pro Windows zabalený přímo v projektu
(`app/php`) — cílový počítač nepotřebuje mít nic předinstalované.

## 📁 Struktura projektu

```
DomaciRozpocet/
├── START.bat / STOP.bat   – spuštění a ukončení aplikace
├── README.txt             – podrobný český návod k použití
├── app/                   – zdrojový kód a přenosné PHP
├── data/                  – databáze a zálohy (nesdílí se do gitu)
├── uploads/                – nahrané doklady
└── exporty/                – vygenerované exporty
```

## 📜 Licence

Tento projekt je **open-source** a je možné jej volně používat, upravovat i
dále šířit — viz soubor [`LICENSE`](LICENSE) (MIT).

## 🙋 O původu aplikace

Aplikace vznikla proto, že manželka chtěla mít konečně pořádný přehled o
rodinném rozpočtu — a excelová tabulka to už dál neutáhla. Víc o tom (a proč
je to vlastně vtipný příběh) najdete přímo v aplikaci v sekci **O programu**.

---

<div align="center">

Vytvořilo ❤️ **[Mirax Studio](https://www.miraxstudio.cz)**

</div>

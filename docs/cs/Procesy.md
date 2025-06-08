# SkyDocu - Procesy
Tento dokument popisuje procesy v SkyDocu. Zde lze nalézt informace o procesech obecně, o jejich životním cyklu a také informace o editoru procesů v superadministraci.

## Obsah

## `1` O procesech obecně
Procesy v SkyDocu reprezentují žádosti o jednotlivé akce. Technicky jsou reprezentovány formuláři, ve kterých jsou vyplněné údaje o akci. Po odeslání autorem je žádost předána dle schvalovací cesty (workflow) dalšímu uživateli, který následně žádost zpracovává - schvaluje, akceptuje, odmítá, atd.

Každý proces se skládá z jednoho a více kroků. Každý krok je reprezentován formulářem a účastníkem (actor), který žádost zpracovává.

## `2` Životní cyklus procesů
Princip životního cyklu je následující:

1. Žadatel vytvoří žádost
    - dojde k vytvoření k instance procesu
2. Žádost je dle workflow přesunuta na dalšího zpracovatele
    - dojde ke změně zpracovatele
3. Zpracovatel žádost zpracuje
    - akceptace, odmítnutí, archivace, atd.
4. Žádost je dle workflow přesunuta na dalšího zpracovatele
    - pokud je další zpracovatel -> krok 2.
    - pokud není -> krok 5.
5. Žádost je uzavřena
    - dle vyjádření posledního zpracovatele
    - dojde k ukončení instance procesu

Jakmile je instance procesu ukončena, nelze ji obnovit zpět.

## `3` Editor procesů
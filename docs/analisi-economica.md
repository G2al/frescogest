# Analisi economica

Documento interno per interpretare correttamente i dati mostrati nella pagina **Analisi economica**.

## Perimetro dei dati

Il mese selezionato determina il periodo analizzato.

Il filtro **Tipologia cliente** applica questi criteri:

- **Tutta l'attività**: ordini pagati di privati e ristoratori, più forniture ai partner.
- **Tutti i clienti**: ordini pagati di privati e ristoratori, senza partner.
- **Privato**: soltanto ordini pagati dei clienti privati.
- **Ristoratore**: soltanto ordini pagati dei ristoratori.
- **Partner**: soltanto merce fornita ai partner.

Un ordine entra nei calcoli soltanto quando è nello stato **Pagato** e viene attribuito al mese della data di pagamento. Una fornitura partner viene attribuita al mese della data di consegna.

Gli incassi giornalieri dichiarati dal partner servono alla sua analisi dedicata, ma non vengono sommati una seconda volta nell'analisi generale. In questo modo la stessa attività non viene duplicata.

## Indicatori principali

### Ricavi netti

Somma degli imponibili, quindi senza IVA:

`ordini pagati netti + forniture partner nette`

Esempio: un ordine netto da 100 € e una fornitura partner netta da 50 € producono ricavi netti pari a 150 €.

### Ricavi IVA inclusa

Somma degli importi effettivamente incassati, comprensivi di IVA:

`ordini pagati lordi + forniture partner lorde`

Esempio: 100 € netti con IVA 4% corrispondono a 104 € IVA inclusa.

### IVA sulle vendite

IVA applicata alle vendite del periodo:

`ricavi IVA inclusa - ricavi netti`

Il riepilogo IVA la suddivide per aliquota.

### IVA sugli acquisti

IVA già compresa nei costi di acquisto della merce venduta o fornita:

`costo di acquisto lordo - costo di acquisto netto`

Il costo di acquisto registrato sul prodotto è comprensivo di IVA; il sistema ne ricava la componente netta per i calcoli economici.

### Saldo IVA

Differenza indicativa fra IVA sulle vendite e IVA sugli acquisti:

`IVA vendite - IVA acquisti`

Un valore positivo indica IVA vendite superiore all'IVA compresa negli acquisti considerati. Non sostituisce la liquidazione fiscale del commercialista.

### Sconti concessi

Somma degli sconti netti applicati alle bolle degli ordini pagati. Lo sconto riduce il ricavo effettivo del documento e viene conservato nello storico.

### Food cost netto

Costo netto della merce effettivamente venduta:

`costo netto prodotti negli ordini pagati + costo netto prodotti forniti ai partner`

Esempio: 10 kg di mele acquistate a 1 € netto al kg generano un food cost di 10 €.

### Margine lordo

Differenza tra ricavi netti e costo netto della merce:

`ricavi netti - food cost netto`

Esempio: vendita netta 20 €, food cost 10 €, margine lordo 10 €.

### Margine percentuale

Incidenza del margine lordo sui ricavi netti:

`margine lordo / ricavi netti × 100`

Nell'esempio precedente il margine percentuale è 50%.

Questa percentuale non è il ricarico sul costo. Se un prodotto costa 10 € e viene venduto a 20 €, il ricarico è 100%, mentre il margine sui ricavi è 50%.

### Costi extra

Somma dei movimenti registrati nella sezione **Costi Extra** nel mese selezionato, per esempio carburante, manutenzione, assicurazione o altre spese operative.

### Costo del personale

Costo dei dipendenti calcolato secondo il tipo di compenso:

- **Orario**: ore lavorate effettive × paga oraria.
- **Giornaliero**: paga giornaliera per ciascun giorno lavorato.
- **Mensile**: paga mensile, proporzionata se il rapporto comincia o termina durante il mese.

Un turno iniziato ma non ancora terminato resta **In corso** e non genera compenso definitivo finché non viene indicata l'ora di fine.

### Costi operativi

`costi extra + costo del personale`

### Risultato reale

Risultato gestionale dopo i costi operativi:

`margine lordo - costi extra - costo del personale`

Esempio: margine lordo 1.000 €, costi extra 200 €, personale 300 €, risultato reale 500 €.

## Tabelle di dettaglio

### Riepilogo IVA

Per ogni aliquota mostra:

- vendite nette;
- IVA sulle vendite;
- acquisti netti riferiti alla merce venduta;
- IVA compresa negli acquisti;
- saldo IVA;
- vendite IVA inclusa.

### Redditività per prodotto

Raggruppa le quantità vendute o fornite per prodotto e confronta:

`ricavi netti - food cost netto = margine`

Il margine percentuale è calcolato sui ricavi netti.

### Redditività per categoria

Applica gli stessi calcoli aggregando i prodotti per categoria. Serve a confrontare quali famiglie merceologiche producono più margine.

### Forniture per destinatario

Raggruppa ordini e forniture per cliente o partner e mostra numero di movimenti, ricavi e margine generato nel periodo.

## Esempio completo

Antonio acquista 10 kg di mele a 1 € netto al kg con IVA 4%:

- costo netto: 10 €;
- IVA acquisti: 0,40 €;
- costo realmente pagato: 10,40 €.

Le rivende a 2 € netti al kg:

- ricavo netto: 20 €;
- IVA vendite: 0,80 €;
- incasso lordo: 20,80 €;
- margine lordo: 10 €;
- margine sui ricavi: 50%;
- saldo IVA indicativo: 0,40 €.

Se nello stesso periodo sono presenti 2 € di costi extra e 3 € di costo del personale:

- costi operativi: 5 €;
- risultato reale: 5 €.

## Controlli consigliati

- Registrare correttamente aliquota e costo di acquisto IVA inclusa su ogni prodotto.
- Chiudere i turni dei dipendenti per consolidare ore e compensi.
- Impostare un ordine come pagato soltanto quando il pagamento è realmente avvenuto.
- Verificare data di pagamento, data di consegna partner e mese selezionato.
- Non usare questo riepilogo come sostituto della contabilità fiscale ufficiale.

<?php

return [
    'terms' => [
        'title' => 'Användarvillkor',
        'body' => 'Du behöver ha fyllt 18 år för att skicka in en berättelse eller kommentar. Berätta om dina egna erfarenheter och dela bara text som du har rätt att publicera. En moderator läser texten före publicering. Publicerade berättelser kan läsas av alla, delas med en länk och synas i sökmotorer.',
        'sections' => [
            [
                'heading' => 'Vad händer med din text?',
                'text' => 'Du kan skriva utan konto. När du skickar in texten tillåter du att den granskas och att den godkända versionen publiceras. Du behåller rättigheterna till din text. Publicering, fortsatt tillgänglighet och en viss granskningstid kan inte garanteras.',
            ],
            [
                'heading' => 'Hantera ditt inlägg',
                'text' => 'Förvara återställningskoden där bara du kommer åt den. Du kan dra tillbaka din berättelse från sidan där du hanterar den. Om en moderator har dolt inlägget kan du där förklara varför du vill att beslutet omprövas.',
            ],
        ],
    ],
    'community' => [
        'title' => 'Community riktlinjer',
        'body' => 'Det finns en människa bakom varje berättelse. Utgå från dina egna erfarenheter och känn efter om skribenten vill ha råd. Hot, trakasserier, förolämpningar, skräppost och uppgifter som avslöjar vem någon annan är hör inte hemma här. Använd anmälningsfunktionen vid texten om något oroar dig.',
        'sections' => [
            [
                'heading' => 'När du svarar någon',
                'text' => 'Om du inte håller med, berätta vilken tanke du ser annorlunda på. Undvik att ställa diagnoser, gissa vem som skriver eller kräva bevis för en upplevelse. Berätta gärna om egna erfarenheter.',
            ],
            [
                'heading' => 'Värna andras privatliv',
                'text' => 'Utelämna detaljer som kan göra någon igenkännbar när du återger ett privat samtal eller en händelse. Var särskilt försiktig med barns skola, adress och rutiner. Anmäl ett problematiskt svar vid själva svaret.',
            ],
        ],
    ],
    'privacy' => [
        'title' => 'Sekretessmeddelande',
        'body' => 'Vi sparar texten du skickar in, ämnet du väljer och det namn du eventuellt vill visa. Moderatorer kan läsa texten före publicering. Om du skapar ett konto används din e-postadress för inloggning. Även om du utelämnar ditt namn kan detaljer i texten göra dig eller någon annan igenkännbar.',
        'sections' => [
            [
                'heading' => 'Vem ser vad?',
                'text' => 'Läsare ser den godkända texten och namnet som visas. Behöriga moderatorer granskar inskickade texter, uppgifter i anmälningar och överklaganden. Om du har ett konto används din e-postadress för inloggning, återställning och de aviseringar du väljer.',
            ],
            [
                'heading' => 'Lässtatistik och åtkomst',
                'text' => 'Om du samtycker används ett separat hashvärde per berättelse för att känna igen upprepade öppningar i samma webbläsare. För åtkomst via enheten och återställning används separata uppgifter. Att dra tillbaka ett inlägg och att begära radering av personuppgifter är olika åtgärder.',
            ],
            [
                'heading' => 'När du anmäler något',
                'text' => 'Din förklaring hjälper moderatorn att bedöma innehållet och det skäl du valt. Den publiceras inte som en kommentar. Frivilliga e-postaviseringar kan stängas av i kontoinställningarna. Använd sidan för integritetsförfrågningar om du vill veta hur länge uppgifter sparas eller vilka som tar emot dem.',
            ],
        ],
    ],
    'cookies' => [
        'title' => 'Cookiemeddelande',
        'body' => 'Webbläsarkakor sparar session och språk. Läsarkakan används bara om du tillåter det, i högst :retention_days dagar, för att räkna öppningar av offentliga bekännelser. Du kan läsa allt även om du tackar nej. Hashen raderas efter :retention_days dagars inaktivitet; räknaren mäter sidöppningar, inte fullständig läsning.',
        'sections' => [
            [
                'heading' => 'På en delad enhet',
                'text' => 'Om du låter webbplatsen komma ihåg enheten kan någon som använder samma webbläsare hantera inlägget. Låt valet vara avstängt på delade enheter. Spara återställningskoden innan du rensar webbläsarens data.',
            ],
            [
                'heading' => 'Ändra ditt val',
                'text' => 'Med knapparna nedan kan du tillåta eller stoppa framtida läsmätning i den här webbläsaren. Valet innebär inget samtycke till reklam. I en annan webbläsare kan du behöva välja på nytt.',
            ],
        ],
    ],
    'illegal_content' => [
        'title' => 'Rapportera olagligt innehåll',
        'body' => 'Om du tror att en berättelse eller kommentar strider mot lagen, öppna anmälningsmenyn vid texten och välj alternativet för olagligt innehåll. Ange vilket avsnitt det gäller och förklara varför. Skicka inte med återställningskoder eller ovidkommande personuppgifter.',
        'sections' => [
            [
                'heading' => 'Vad behöver stå i anmälan?',
                'text' => 'Välj integritet, upphovsrätt, domstolsbeslut eller annat skäl. Förklara vilken rättighet du anser att texten kränker och hur. Ange land, originalverk eller beslut om det är relevant. Upprepa inte känsliga uppgifter i sin helhet.',
            ],
            [
                'heading' => 'När du har anmält',
                'text' => 'Anmälan kopplas till innehållet du valt. Efter granskningen kan innehållet döljas eller ligga kvar. Använd den vanliga anmälan för brott mot gemenskapsreglerna. Kontakta lokal larmtjänst direkt vid omedelbar fara.',
            ],
        ],
    ],
    'data_request' => [
        'title' => 'Sekretessförfrågan',
        'body' => 'Du kan dra tillbaka din berättelse på sidan där du hanterar den. Har du förlorat åtkomsten kan du använda din sparade återställningskod. Berättelsen visas sedan inte offentligt på :site_name. Kopior på andra platser och sökträffar kan finnas kvar. Anmäl inlägget om någon annan har publicerat dina personuppgifter.',
        'sections' => [
            [
                'heading' => 'Förbered din begäran',
                'text' => 'Skriv till integritetskontakten nedan och beskriv vad du vill och vilka uppgifter det gäller. En offentlig länk, språk och ungefärligt datum kan räcka. Skicka inga lösenord, återställningskoder eller id-kopior i ditt första meddelande.',
            ],
            [
                'heading' => 'Så behandlas din begäran',
                'text' => 'Vi kan behöva fler uppgifter för att undvika att lämna ut någon annans data. När GDPR gäller är den normala svarstiden en månad. Om en förlängning enligt lag behövs får du veta varför under den första månaden.',
            ],
        ],
    ],
    'operator' => ['title' => 'Tjänsteoperatör', 'body' => ':site_domain publicerar anonyma berättelser och svar från läsare. Moderatorerna granskar inskickade texter och hanterar anmälningar. Använd anmälningsfunktionen vid inlägget om du vill uppmärksamma oss på en viss text.'],
    'rights' => 'Beroende på vilken lag som gäller kan du begära tillgång till dina uppgifter, rättelse, radering eller begränsning av behandlingen, invända mot behandlingen och klaga hos en dataskyddsmyndighet. Ange vilka uppgifter det gäller och vad du vill ska göras.',
    'contact_unavailable' => 'Kontaktadressen för integritetsfrågor har ännu inte publicerats.',
    'contact_label' => 'Kontakt för dataskyddsfrågor',
];

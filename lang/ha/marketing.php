<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'AgriShield AI | Ka san kowane fili. Ka yanke shawara da tabbaci.',
        'description' => 'AgriShield yana haɗa sassan gona, tsarin amfanin gona, yanayi, ƙasa da shaidar fili domin manoma da ƙungiyoyin noma su yanke shawara da tabbaci.',
    ],
    'language' => ['label' => 'Harshe', 'current' => 'Hausa', 'en' => 'English', 'ha' => 'Hausa', 'fr' => 'Français'],
    'nav' => [
        'explore' => 'Bincika AgriShield', 'home' => 'Farko', 'platform' => 'Dandali', 'field_voice' => 'Muryar Fili', 'how' => 'Yadda yake aiki', 'about' => 'Game da mu', 'work' => 'Yi aiki da mu', 'sign_in' => 'Shiga', 'open_platform' => 'Buɗe dandali', 'open_workspace' => 'Buɗe wurin aiki', 'plan' => 'Shirya amfani da shi', 'menu' => 'Jeri', 'close' => 'Rufe', 'open_menu' => 'Buɗe babban jeri', 'close_menu' => 'Rufe babban jeri',
    ],
    'home' => [
        'eyebrow' => 'Bayanan noma na zamani ga kowace gona',
        'headline' => 'Ka ga kowane fili. Ka san abin da ke bukatar kulawa.',
        'lede' => 'AgriShield yana haɗa iyakar gona, sassan amfanin gona, yanayi, ƙasa da shaidar manomi a wuri guda—domin kowace shawara ta fara da cikakken bayani.',
        'primary_cta' => 'Shirya amfani da shi', 'secondary_cta' => 'Bincika dandalin',
        'hero_image_alt' => 'Manomi yana duba amfanin gona a Jigawa, Najeriya', 'hero_caption' => 'Binciken fili · Jigawa, Najeriya',
        'field_plan' => 'Tsarin gona kai tsaye', 'field_model_title' => 'Tsara kowane ɓangaren gona bisa amfanin da aka shuka a wurin.', 'field_model_body' => 'Tsarin filin yana da wurinsa. Ƙayyade kowane sashe, sanya amfanin gona, sannan ka haɗa kowace alama da sashen da take bayyanawa.', 'field_name' => 'Gonar Arewa', 'context_ready' => 'Bayanai sun shirya',
        'sections' => [
            ['name' => 'Sashe A', 'crop' => 'Albasa', 'area' => '1.4 ha'],
            ['name' => 'Sashe B', 'crop' => 'Tumatir', 'area' => '0.9 ha'],
            ['name' => 'Sashe C', 'crop' => 'Masara', 'area' => '2.1 ha'],
        ],
        'allocated' => 'An raba hekta 4.4 cikin 5.2',
        'proof_label' => 'Cikakken hoton aikin gona a wuri guda',
        'proof' => ['Sassan gona', 'Lokutan noma', 'Yanayi da ƙasa', 'Shaidar fili', 'Shawarwari'],
        'audience_eyebrow' => 'An gina shi domin masu aikin gona',
        'audience_title' => 'Manoma da ƙungiyoyin fili suna aiki daga rikodi guda.',
        'audience_intro' => 'Manoma suna bukatar amsoshi masu sauƙi a kan lokaci. Ƙungiyoyin shiri da jami’an faɗaɗa noma suna bukatar sahihan bayanai da bin diddigi. AgriShield yana haɗa su.',
        'audiences' => [
            ['label' => 'Ga manoma', 'title' => 'Tsara gona sashe bayan sashe.', 'body' => 'Raba gona zuwa sassan da kake bukata, sanya amfanin gona ga kowanne, sannan ka haɗa yanayi, ƙasa da binciken amfanin gona da wurin da ya dace.', 'items' => ['Sanya wa kowane sashe suna', 'Zaɓi albasa, tumatir, masara ko wani amfanin gona', 'Duba filin da aka raba da wanda ya rage']],
            ['label' => 'Ga ƙungiyoyin noma', 'title' => 'Gano inda ake bukatar taimako.', 'body' => 'Duba shaidar fili, fahimci halin amfanin gona, sannan a tura matsala ga jami’in da ya dace ba tare da sake haɗa bayanai daga saƙonni ba.', 'items' => ['Jerin abubuwan da suka fi gaggawa', 'Shaida a haɗe da gona', 'Bita da amincewa a bayyane']],
        ],
        'capabilities_eyebrow' => 'Bayanan fili masu amfani', 'capabilities_title' => 'Alamomi da ayyukan da ke goyon bayan kowace shawarar gona.', 'capabilities_intro' => 'AgriShield yana haɗa bayanan tauraron ɗan adam, shaidar manomi da ayyukan tallafi a rikodin gona guda. Ana nuna tushe, matsayi da mataki na gaba.', 'capabilities_note' => 'Samuwar aiki ta danganta da masu samar da bayanai da aka saita.',
        'capabilities' => [
            ['visual' => 'satellite', 'code' => 'TAURARO / NDVI', 'title' => 'Sa ido ta tauraron ɗan adam', 'body' => 'Duba bayanan tauraron ɗan adam da alamomin NDVI na ciyayi bisa gona, sashe da rana.', 'value' => '0.72', 'label' => 'NDVI · alama mai kyau'],
            ['visual' => 'soil', 'code' => 'ƘASA / DANSHI', 'title' => 'Lafiyar ƙasa da danshi', 'body' => 'Haɗa danshi, pH, nitrogen, phosphorus, potassium da organic carbon da ake goyon baya.', 'value' => '31%', 'label' => 'Danshin ƙasa'],
            ['visual' => 'diagnosis', 'code' => 'HOTO / BINCIKE', 'title' => 'Binciken hoton amfanin gona', 'body' => 'Aika hoton amfanin gona, a riƙe abin da aka gano da tabbaci, sannan a bar shawara a buɗe don bita.', 'value' => 'An karɓi hoto', 'label' => 'Ana kan bita'],
            ['visual' => 'voice', 'code' => 'MURYA / SAƘON FILI', 'title' => 'Saƙon murya daga fili', 'body' => 'Manomi zai bayyana matsalar amfanin gona da murya, a riƙe rubutun da shawara tare da gona.', 'value' => '00:18', 'label' => 'Saƙon murya na Hausa'],
            ['visual' => 'finance', 'code' => 'KUƊI / SAMUN RANCE', 'title' => 'Samun rancen gona', 'body' => 'Bincika kayayyakin kuɗi da aka saita sannan a aika buƙatar da ke haɗe da gona don bitar abokin hulɗa.', 'value' => '₦350K', 'label' => 'An aika buƙata'],
            ['visual' => 'weather', 'code' => 'YANAYI / SHAWARA', 'title' => 'Hadarin yanayi da shawarwari', 'body' => 'Mayar da alamomin ruwan sama da hasashe zuwa shawara mai fifiko, mai alhaki da amincewa.', 'value' => '68%', 'label' => 'Yiwuwar ruwa · awa 24'],
        ],
        'flow_eyebrow' => 'Daga alamar fili zuwa aiki', 'flow_title' => 'Hanya mai sauƙi daga “me ya canza?” zuwa “me za a yi yanzu?”', 'flow_intro' => 'Dandalin yana bin yadda ake yanke shawara a fili. Kowane mataki yana riƙe gona, amfanin gona da shaidar da ke goyon baya tare.',
        'steps' => [
            ['number' => '01', 'label' => 'Taswira', 'title' => 'Yi rajistar gona da sassanta', 'body' => 'Ɗauki iyaka, jimillar fili da tsarin amfanin gona na kowane sashe.'],
            ['number' => '02', 'label' => 'Sa ido', 'title' => 'Haɗa alamomi da bayani', 'body' => 'Duba yanayi, ƙasa da bayanan da ake goyon baya tare da amfanin gona.'],
            ['number' => '03', 'label' => 'Bita', 'title' => 'Fitar da abin da ke bukatar kulawa', 'body' => 'Jera matsalolin amfanin gona bisa tsanani, sabunta bayanai da wuri.'],
            ['number' => '04', 'label' => 'Aiki', 'title' => 'Ba mataki na gaba mai alhaki', 'body' => 'Buga shawara mai amfani sannan a riƙe amincewa da bin diddigi.'],
        ],
        'product_eyebrow' => 'Kwarewar amfani', 'product_title' => 'Tsarin gona yana nan a bayyane duk inda aiki ya koma.', 'product_body' => 'Manomi zai iya sarrafa sassan gona a waya, yayin da jami’in faɗaɗa noma yake ganin rabon amfanin gona da alamomin kulawa daga wurin aiki.',
        'dashboard' => ['title' => 'Bayanin fili', 'season' => 'Lokacin noma na 2026', 'farm' => 'Gonar Arewa', 'status' => 'Bayanan gona sun shirya', 'sections' => 'Sassa 3', 'rain' => 'Yiwuwar ruwa 68%', 'attention' => 'Yana bukatar kulawa', 'alert_title' => 'Ruwan sama mai yawa na iya shafar sashe na ƙasa.', 'alert_body' => 'A duba magudanar ruwa kafin aikin fili na gaba.', 'action' => 'Duba filin', 'note' => 'Misalin manhaja · bayanan gwaji'],
        'field_eyebrow' => 'An tsara shi domin aikin fili na gaske', 'field_title' => 'Fasaha ta sa shawarar fili ta gaba ta fi sauƙi.', 'field_body' => 'An tsara AgriShield domin wuraren da sadarwa ba ta da ƙarfi, tattara bayanai cikin sauƙi da ƙungiyoyin da ke kula da gonaki da yawa. Yana nuna sabuntawar bayanai, matsayin mai samarwa da bitar ɗan adam.',
        'field_points' => [
            ['title' => 'Fili a gaba', 'body' => 'Hanyoyin waya masu sauƙi don rajistar gona, sassa, hotuna da murya.'],
            ['title' => 'Cikakken bayani', 'body' => 'Kowace alama tana haɗe da gona, amfanin gona da lokacin noma.'],
            ['title' => 'Aiki mai alhaki', 'body' => 'Matsayin bita, wanda ke da alhaki da amincewa suna bayyane.'],
        ],
        'field_image_alt' => 'Gonakin noma a Hawul, Jihar Borno', 'field_image_caption' => 'Gonakin noma · Hawul, Jihar Borno',
        'language_eyebrow' => 'Harshe yana cikin samun dama', 'language_title' => 'Cikakken labarin fili iri ɗaya a Turanci, Hausa da Faransanci.', 'language_body' => 'Ƙungiyoyi za su iya nuna shafin a harshen da ya fi dacewa da manoma, abokan hulɗa da shirye-shiryen yanki—ba tare da canza ma’anar samfurin ba.',
        'language_cards' => [
            ['code' => 'EN', 'name' => 'English', 'sample' => 'See every field. Know what needs attention.'],
            ['code' => 'HA', 'name' => 'Hausa', 'sample' => 'Ka ga kowane fili. Ka san abin da ke bukatar kulawa.'],
            ['code' => 'FR', 'name' => 'Français', 'sample' => 'Voyez chaque parcelle. Sachez où agir.'],
        ],
        'faq_eyebrow' => 'Kafin fara amfani', 'faq_title' => 'Amsoshi masu sauƙi kafin farawa.',
        'faqs' => [
            ['question' => 'Gona guda za ta iya samun amfanin gona daban-daban?', 'answer' => 'Eh. Manomi zai iya ƙirƙirar sassa gwargwadon girman gona, ya sanya wa kowanne amfanin gona da girmansa.'],
            ['question' => 'AgriShield yana aiki inda sadarwa ba ta da ƙarfi?', 'answer' => 'An tsara manhajar waya domin amfani a fili kuma tana iya riƙe bayanan da aka adana. Bukatar sadarwa ta danganta da mai samar da bayanai da aikin da ake yi.'],
            ['question' => 'Dandalin yana ƙirƙirar shawara idan babu bayanai?', 'answer' => 'A’a. Ana nuna sabuntawar bayanai, matsayin mai samarwa da matsayin bita. Shawara tana dogara da shaidar da masu samar da bayanai da aka saita.'],
        ],
        'closing_label' => 'Fara da shiri guda mai ma’ana', 'closing_title' => 'Ku kawo gonaki, amfanin gona da shawarar da kuke son tallafawa.', 'closing_body' => 'Za mu tsara aikin fili, hanyoyin bayanai, bukatun harshe da matakai masu alhaki tare da ƙungiyarku.', 'closing_cta' => 'Tattauna yadda za a fara',
    ],
    'pages' => [
        'common' => ['cta_eyebrow' => 'Fara da tsarin amfanin gona guda', 'cta_title' => 'Faɗa mana inda tallafin fili yake samun tsaiko.', 'cta_body' => 'Ku kawo mana amfanin gona guda, yanki guda ko ƙalubalen aikin faɗaɗa noma.', 'cta_action' => 'Fara tattaunawa'],
        'about' => [
            'meta_title' => 'Game da mu | AgriShield AI', 'meta_description' => 'Dalilin da ya sa AgriShield ke gina bayanan noma masu alhaki.', 'eyebrow' => 'Game da AgriShield', 'title' => 'Tallafin amfanin gona yana bukatar tarihin aiki.', 'intro' => 'Muna gina bayanan gona masu amfani ga masu fahimtar fili da bin mataki na gaba.',
            'statement_label' => 'Dalilin wannan samfurin', 'statement_title' => 'Muhimman bayanan fili kada su ɓace tsakanin saƙonni.', 'statement_body' => ['Iyakokin gona, sassan amfanin gona, bayanan tauraron ɗan adam, murya da hotuna sukan watse a wurare daban-daban.', 'AgriShield yana haɗa su a rikodin gona guda domin a ga abin da ya canza, abin da aka duba da mataki na gaba.'],
            'principles_label' => 'Tsarin samfur', 'principles_title' => 'Ka’idoji huɗu ne ke jagorantar samfurin.', 'principles' => [
                ['code' => 'FILI', 'title' => 'Bayanan fili a gaba', 'body' => 'Kowace alama tana haɗe da gona, sashe da lokacin noma.'],
                ['code' => 'SIRRI', 'title' => 'Rikodi mai kariya', 'body' => 'Bayanan gona, murya da hoto suna cikin aikin ƙungiyar da aka ba izini.'],
                ['code' => 'BAYYANE', 'title' => 'Iyaka a bayyane', 'body' => 'Matsayin mai samarwa, sabuntawar bayanai da bitar ɗan adam suna bayyane.'],
                ['code' => 'AIKI', 'title' => 'Bin diddigi mai alhaki', 'body' => 'Matsaloli, shawarwari da amincewa suna barin tarihin aiki.'],
            ],
            'team_label' => 'Aikin da ke bayan AgriShield', 'team_title' => 'Aikin noma, ingantaccen software da aiwatarwa a hankali ne ke tsara aikin.', 'team_body' => 'Samfurin yana haɗa kwarewar fili da amintaccen injiniyanci da tallafin farawa.',
        ],
        'solutions' => [
            'meta_title' => 'Dandali | AgriShield AI', 'meta_description' => 'Bincika bayanan tauraron ɗan adam, sassan gona, hoto, murya, danshin ƙasa, yanayi da kuɗin gona.', 'eyebrow' => 'Dandalin AgriShield', 'title' => 'Riƙe kowace alamar gona a cikin rikodin aiki guda.', 'intro' => 'Cikakken hoton aiki na gonaki, sassa, bayanan nesa, shaidar manomi da bin diddigi.',
            'overview_label' => 'Haɗaɗɗun bayanan gona', 'overview_title' => 'Ga filin, fahimci alama, mallaki mataki na gaba.', 'overview_body' => 'Kowane aiki yana shiga rikodin gona guda maimakon wani dandali dabam.',
            'features' => [
                ['code' => '01 / TASWIRA', 'title' => 'Gonaki da sassan amfanin gona', 'body' => 'Ƙirƙiri sassan da ake bukata, sanya amfanin gona da bibiyar fili.', 'items' => ['Tabbatattun iyaka', 'Amfanin gona na sashe', 'Tarihin lokaci']],
                ['code' => '02 / LURA', 'title' => 'Tauraron ɗan adam da NDVI', 'body' => 'Duba alamomin ciyayi bisa gona, sashe da rana.', 'items' => ['Alamomin NDVI', 'Ranar lura', 'Matsayin mai samarwa']],
                ['code' => '03 / ƘASA', 'title' => 'Danshin ƙasa da yanayi', 'body' => 'Duba bayanan ƙasa da hasashe tare da shirin amfanin gona.', 'items' => ['Danshi', 'Alamomin ƙasa', 'Hadarin ruwan sama']],
                ['code' => '04 / RAHOTO', 'title' => 'Binciken hoto da Muryar Fili', 'body' => 'Ɗauki hoton amfanin gona ko saƙon murya a haɗa da gonar da ta dace.', 'items' => ['Shaida mai sirri', 'Tallafin harshe', 'Matsayin bita']],
                ['code' => '05 / AIKI', 'title' => 'Shawara da bin diddigi', 'body' => 'Buga mataki mai amfani tare da mai alhaki da amincewa.', 'items' => ['Fifiko da lokaci', 'Mai alhaki', 'Tarihin amincewa']],
                ['code' => '06 / SAMU', 'title' => 'Rancen gona da ayyuka', 'body' => 'Duba kuɗin da aka saita sannan a aika buƙatar da ke haɗe da gona.', 'items' => ['Kayayyakin da suka dace', 'Buƙatar gona', 'Matsayin aikace-aikace']],
            ],
            'provider_label' => 'An tsara shi da sanin masu samarwa', 'provider_title' => 'Ayyuka na musamman suna haɗuwa ba tare da ɓoye tushensu ba.', 'provider_body' => 'Tauraron ɗan adam, harshe, binciken amfanin gona, yanayi da kuɗi suna nuna samuwa da matsayinsu. Rikodin gona yana ci gaba da amfani idan sabis na waje bai samu ba.',
        ],
        'impact' => [
            'meta_title' => 'Yadda yake aiki | AgriShield AI', 'meta_description' => 'Daga rajistar gona zuwa shaida, bita, shawara da amincewa.', 'eyebrow' => 'Tsarin aiki', 'title' => 'Daga rikodin gona zuwa aikin da aka amince da shi.', 'intro' => 'Tsari mai sauƙi yana haɗa muhimman matakan tallafin amfanin gona tare da nuna gibin bayanai da matsayin bita.',
            'steps' => [
                ['number' => '01', 'label' => 'Kafa bayani', 'title' => 'Yi rajistar gona, sassa da lokacin noma', 'body' => 'Ba kowace alama ƙungiya, mai alhaki, iyaka da tsarin amfanin gona.', 'note' => 'Gonaki · sassa · lokutan noma'],
                ['number' => '02', 'label' => 'Sa ido', 'title' => 'Haɗa tauraron ɗan adam, ƙasa da yanayi', 'body' => 'Kwatanta bayanai da amfanin gona da sashen da suke bayyanawa.', 'note' => 'NDVI · danshin ƙasa · yanayi'],
                ['number' => '03', 'label' => 'Riƙe shaida', 'title' => 'Mayar da rahoton fili zuwa matsala da za a duba', 'body' => 'Riƙe murya, hotuna da abin da aka gano cikin wurin aiki mai izini.', 'note' => 'Muryar Fili · binciken hoto'],
                ['number' => '04', 'label' => 'Kammala aiki', 'title' => 'Buga mataki sannan a rubuta amincewa', 'body' => 'Riƙe tarihin shawara, mai alhaki da ko an amince da ita.', 'note' => 'Shawarwari · bin diddigi'],
            ],
        ],
        'partners' => [
            'meta_title' => 'Yi aiki da AgriShield | AgriShield AI', 'meta_description' => 'Shirya amfani da AgriShield ga shirin noma ko ƙungiyar faɗaɗa noma.', 'eyebrow' => 'Yi aiki da mu', 'title' => 'Fara da aikin fili da ƙungiyarku ta riga ta mallaka.', 'intro' => 'Muna aiki mafi kyau da ƙungiyoyi masu al’ummar manoma, ƙungiyar fili da alhakin bin diddigi.',
            'starting_label' => 'Wuraren farawa', 'starting_title' => 'Fara da gibin aiki guda.', 'starting_body' => 'Aiki mai mayar da hankali yana fayyace alhaki, tushen bayanai da ma’aunin nasara.', 'starting_points' => ['Gina ingantaccen rajistar gonaki da sassa', 'Sa ido kan tauraron ɗan adam, ƙasa ko yanayi', 'Tura rahoton murya da hoto ga ƙungiyar da ta dace', 'Haɗa manoman da suka cancanta da abokan kuɗi', 'Buga shawarwari da rubuta amincewa'],
            'proof_label' => 'Bayyanannun rawar aiki', 'proof_title' => 'Kowane fara aiki yana da sunayen masu alhaki.', 'proof_body' => 'Kafin aikin fili ya fara, ƙungiyar tana amincewa da iyaka, hanyoyin bayanai, masu samar da sabis, masu bita da hanyar tura matsala.',
        ],
        'team' => [
            'meta_title' => 'Ƙungiya | AgriShield AI', 'meta_description' => 'Kwarewar da ke bayan AgriShield.', 'eyebrow' => 'Ƙungiya', 'title' => 'Kwarewar noma, bayanai da aikin fili suna aiki tare.', 'intro' => 'Masu aikin shirye-shiryen noma, bayanan taswira, injiniyan samfur da aiwatar da aikin fili ne suke gina AgriShield.',
            'disciplines_label' => 'Kwarewar yanzu', 'disciplines_title' => 'Kwarewar da ake bukata don isar da samfurin.', 'disciplines' => [
                ['code' => '01', 'title' => 'Tsarin shirin noma', 'body' => 'Mayar da alhakin fili da shawarar amfanin gona zuwa hanyar aiki.'],
                ['code' => '02', 'title' => 'Tsarin taswira da bayanai', 'body' => 'Haɗa iyaka da bayanan tauraron ɗan adam da matsayin mai samarwa.'],
                ['code' => '03', 'title' => 'Injiniyan samfur', 'body' => 'Kula da API, manhajar waya, dandalin yanar gizo da iko.'],
                ['code' => '04', 'title' => 'Aiwatar da fili', 'body' => 'Tallafa fara amfani, rikodi, harshe da karɓar tsarin aiki.'],
            ],
        ],
        'contact' => [
            'meta_title' => 'Tuntuɓa | AgriShield AI Ltd', 'meta_description' => 'Tuntuɓi AgriShield don tattauna amfani da bayanan gona.', 'eyebrow' => 'Tuntuɓa', 'title' => 'Ku kawo mana matsalar tallafin amfanin gona.', 'intro' => 'Faɗa mana inda manoma ko jami’an faɗaɗa noma suke samun matsala. Za mu taimaka tsara matakin farko.',
            'form_label' => 'Fara tattaunawa', 'form_title' => 'A shirye muke mu saurara.', 'form_body' => 'Don tsara aiki, nuna samfur, hulɗa ko tambayar hukuma, tuntuɓi ƙungiyarmu.', 'email' => 'Imel', 'region' => 'Yankin aiki', 'region_value' => 'Arewacin Najeriya', 'name' => 'Sunanka', 'name_placeholder' => 'Cikakken suna', 'work_email' => 'Imel na aiki', 'organisation' => 'Ƙungiya', 'organisation_placeholder' => 'Sunan ƙungiya', 'interest' => 'Ina sha’awar', 'message' => 'Ta yaya za mu taimaka?', 'message_placeholder' => 'Faɗa mana gonaki, masu amfani da sakamakon da kuke bukata.', 'options' => ['Neman nuna samfur', 'Shirya fara amfani', 'Binciken haɗin bayanai ko kuɗi', 'Tambaya ta gama gari'], 'submit' => 'Shirya imel', 'privacy' => 'Wannan yana buɗe manhajar imel. Ba a ajiye bayanai a wannan shafin.'
        ],
        'field_voice' => [
            'meta_title' => 'Muryar Fili | AgriShield AI', 'meta_description' => 'Ɗauki tambayar manomi da murya tare da bayanan gona.', 'eyebrow' => 'Muryar Fili / karɓa a shirye', 'title' => 'Ɗauki tambayar. Riƙe cikakken bayani.', 'intro' => 'Yi rikodi ko ɗora tambayar manomi, haɗa ta da gona sannan a riƙe tarihin matsala mai sirri.',
            'workflow_label' => 'Tsarin da yake samuwa', 'workflow_title' => 'An gina shi domin waya a fili.', 'steps' => [
                ['title' => 'Yi rikodi ko ɗora', 'body' => 'Ɗauki gajeren saƙon murya ko amfani da fayil da aka ajiye.'],
                ['title' => 'Haɗa bayanan gona', 'body' => 'Haɗa tambayar da gonar da aka ba izini da harsunan da aka zaɓa.'],
                ['title' => 'Riƙe matsalar a sirri', 'body' => 'Sauti da tarihi suna cikin wurin aikin ƙungiya.'],
                ['title' => 'Tura don bita', 'body' => 'Ba ƙungiyar fili wuri mai sauƙi don duba da ci gaba.'],
            ],
            'panel_badge' => 'Wurin aikin ƙungiya mai tsaro', 'panel_title' => 'Ana iya karɓar murya yanzu', 'panel_body' => 'Rubutawa, fassara da samar da shawara suna aiki idan an saita mai samar da murya.', 'panel_action' => 'Buɗe Muryar Fili', 'panel_signin' => 'Shiga don ci gaba', 'bounds_label' => 'Iyaka a bayyane', 'bounds_title' => 'Abin da aikin yake yi—da abin da ya dogara da mai samarwa.', 'core_title' => 'Babban dandali', 'core_items' => ['Karɓa da kunna sauti a sirri', 'Zaɓin gona da harshe', 'Tarihin ƙungiya', 'Bayyanar matsayi da gazawa'], 'provider_title' => 'Mai samarwa da aka saita', 'provider_items' => ['Rubuta magana', 'Fassarar harshe', 'Samar da shawarar amfanin gona', 'Alamomin tura matsala'],
        ],
    ],
    'footer' => ['eyebrow' => 'AgriShield / Arewacin Najeriya', 'title' => 'Rikodi guda daga tambayar fili zuwa bin diddigi.', 'body' => 'Amintattun bayanan amfanin gona, tambayoyin manoma da isar da shawara mai alhaki ga ƙungiyoyin fili.', 'product' => 'Samfuri', 'company' => 'Kamfani', 'about' => 'Game da mu', 'team' => 'Ƙungiya', 'work' => 'Yi aiki da mu', 'contact' => 'Tuntuɓa', 'disclaimer' => 'ƙwararrun masana yankin su duba shawarar amfanin gona.'],
];

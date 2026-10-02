<?php

declare(strict_types=1);

return [
    'meta' => [
        'title' => 'AgriShield AI | Comprendre chaque parcelle. Agir avec confiance.',
        'description' => 'AgriShield relie les sections de ferme, les cultures, la météo, le sol et les observations terrain pour aider agriculteurs et équipes agricoles à agir avec confiance.',
    ],
    'language' => ['label' => 'Langue', 'current' => 'Français', 'en' => 'English', 'ha' => 'Hausa', 'fr' => 'Français'],
    'nav' => [
        'explore' => 'Découvrir AgriShield', 'home' => 'Accueil', 'platform' => 'Plateforme', 'field_voice' => 'Voix du terrain', 'how' => 'Fonctionnement', 'about' => 'À propos', 'work' => 'Collaborer', 'sign_in' => 'Connexion', 'open_platform' => 'Ouvrir la plateforme', 'open_workspace' => 'Ouvrir l’espace', 'plan' => 'Planifier un déploiement', 'menu' => 'Menu', 'close' => 'Fermer', 'open_menu' => 'Ouvrir le menu principal', 'close_menu' => 'Fermer le menu principal',
    ],
    'home' => [
        'eyebrow' => 'L’intelligence agricole pour chaque ferme',
        'headline' => 'Voyez chaque parcelle. Sachez où agir.',
        'lede' => 'AgriShield réunit limites de ferme, sections cultivées, météo, sol et observations des agriculteurs dans une vue claire—pour que chaque décision parte du contexte complet.',
        'primary_cta' => 'Planifier un déploiement', 'secondary_cta' => 'Découvrir la plateforme',
        'hero_image_alt' => 'Un agriculteur examine une culture à Jigawa, au Nigeria', 'hero_caption' => 'Inspection terrain · Jigawa, Nigeria',
        'field_plan' => 'Plan de ferme en direct', 'field_model_title' => 'Planifiez chaque partie de la ferme selon la culture qui y pousse.', 'field_model_body' => 'Le plan de terrain dispose désormais de son propre espace. Définissez chaque parcelle, affectez sa culture et reliez chaque signal à la section concernée.', 'field_name' => 'Ferme Nord', 'context_ready' => 'Contexte prêt',
        'sections' => [
            ['name' => 'Section A', 'crop' => 'Oignon', 'area' => '1,4 ha'],
            ['name' => 'Section B', 'crop' => 'Tomate', 'area' => '0,9 ha'],
            ['name' => 'Section C', 'crop' => 'Maïs', 'area' => '2,1 ha'],
        ],
        'allocated' => '4,4 hectares sur 5,2 attribués',
        'proof_label' => 'Une vue opérationnelle agricole unique',
        'proof' => ['Sections de ferme', 'Saisons culturales', 'Météo et sol', 'Observations terrain', 'Conseils'],
        'audience_eyebrow' => 'Conçu autour des personnes sur le terrain', 'audience_title' => 'Agriculteurs et équipes terrain travaillent depuis le même dossier.', 'audience_intro' => 'Les agriculteurs ont besoin de réponses simples et rapides. Les programmes et équipes de conseil ont besoin d’un contexte fiable et d’un suivi visible. AgriShield relie les deux.',
        'audiences' => [
            ['label' => 'Pour les agriculteurs', 'title' => 'Planifiez la ferme section par section.', 'body' => 'Divisez la ferme en autant de sections que nécessaire, affectez une culture à chacune et reliez météo, sol et contrôles à la bonne zone.', 'items' => ['Nommer clairement chaque section', 'Affecter oignon, tomate, maïs ou une autre culture', 'Voir la surface attribuée et restante']],
            ['label' => 'Pour les équipes agricoles', 'title' => 'Voyez où le soutien est nécessaire.', 'body' => 'Examinez les observations terrain, comprenez le contexte cultural et orientez les urgences vers le bon conseiller sans reconstruire l’historique depuis des messages.', 'items' => ['File d’attention priorisée', 'Preuves reliées à la ferme', 'Révision et validation visibles']],
        ],
        'capabilities_eyebrow' => 'L’intelligence terrain, rendue utile', 'capabilities_title' => 'Les signaux et services derrière chaque décision agricole.', 'capabilities_intro' => 'AgriShield réunit télédétection, observations des agriculteurs et services opérationnels dans le même dossier de ferme. Chaque capacité affiche sa source, son état et la prochaine action utile.', 'capabilities_note' => 'La disponibilité dépend des fournisseurs configurés pour chaque déploiement.',
        'capabilities' => [
            ['visual' => 'satellite', 'code' => 'SAT / NDVI', 'title' => 'Suivi des cultures par satellite', 'body' => 'Consultez les observations satellite prises en charge et les signaux NDVI par ferme, section et date.', 'value' => '0,72', 'label' => 'NDVI · signal sain'],
            ['visual' => 'soil', 'code' => 'SOL / HUMIDITÉ', 'title' => 'Santé et humidité du sol', 'body' => 'Réunissez les mesures prises en charge : humidité, pH, azote, phosphore, potassium et carbone organique.', 'value' => '31 %', 'label' => 'Humidité du sol'],
            ['visual' => 'diagnosis', 'code' => 'IMAGE / DIAGNOSTIC', 'title' => 'Diagnostic par image', 'body' => 'Envoyez une photo, conservez les détections et leur confiance, puis gardez les recommandations ouvertes à la révision.', 'value' => 'Photo reçue', 'label' => 'Révision en cours'],
            ['visual' => 'voice', 'code' => 'VOIX / NOTE TERRAIN', 'title' => 'Notes vocales du terrain', 'body' => 'Les agriculteurs décrivent un problème par la voix ; transcription et conseil restent reliés à la ferme.', 'value' => '00:18', 'label' => 'Note vocale en haoussa'],
            ['visual' => 'finance', 'code' => 'FINANCE / ACCÈS', 'title' => 'Accès aux prêts agricoles', 'body' => 'Explorez les produits financiers configurés et soumettez une demande liée à la ferme pour étude par le partenaire.', 'value' => '₦350K', 'label' => 'Demande soumise'],
            ['visual' => 'weather', 'code' => 'MÉTÉO / CONSEIL', 'title' => 'Risques météo et conseils', 'body' => 'Transformez pluie et prévisions en conseils priorisés, avec responsable et validation.', 'value' => '68 %', 'label' => 'Risque de pluie · 24 h'],
        ],
        'flow_eyebrow' => 'Du signal terrain à l’action', 'flow_title' => 'Un chemin plus simple de « qu’est-ce qui a changé ? » à « quelle est la suite ? »', 'flow_intro' => 'Le produit suit la réalité des décisions agricoles. Chaque étape garde ensemble la ferme, la culture et les preuves.',
        'steps' => [
            ['number' => '01', 'label' => 'Cartographier', 'title' => 'Enregistrer la ferme et ses sections', 'body' => 'Saisissez les limites, la surface totale et le plan cultural de chaque section.'],
            ['number' => '02', 'label' => 'Suivre', 'title' => 'Mettre les signaux en contexte', 'body' => 'Lisez météo, sol et observations prises en charge à côté de la culture active.'],
            ['number' => '03', 'label' => 'Examiner', 'title' => 'Faire ressortir les priorités', 'body' => 'Classez cas culturaux et risques par gravité, fraîcheur et emplacement.'],
            ['number' => '04', 'label' => 'Agir', 'title' => 'Attribuer la prochaine action', 'body' => 'Publiez des conseils pratiques et conservez validation et suivi.'],
        ],
        'product_eyebrow' => 'Expérience produit', 'product_title' => 'Le plan de ferme reste visible partout où le travail avance.', 'product_body' => 'L’agriculteur gère ses sections sur téléphone tandis que le conseiller voit la même répartition des cultures et les mêmes alertes depuis son espace opérationnel.',
        'dashboard' => ['title' => 'Vue du terrain', 'season' => 'Saison agricole 2026', 'farm' => 'Ferme Nord', 'status' => 'Contexte ferme prêt', 'sections' => '3 sections', 'rain' => '68 % de pluie', 'attention' => 'Attention requise', 'alert_title' => 'De fortes pluies peuvent affecter la section basse.', 'alert_body' => 'Vérifiez le drainage avant la prochaine intervention.', 'action' => 'Examiner le terrain', 'note' => 'Interface illustrative · données d’exemple'],
        'field_eyebrow' => 'Ancré dans les opérations réelles', 'field_title' => 'La technologie doit clarifier la prochaine décision terrain.', 'field_body' => 'AgriShield est conçu pour une connectivité variable, une collecte pratique et des équipes qui suivent de nombreuses fermes. La fraîcheur des données, l’état du fournisseur et la révision humaine restent visibles.',
        'field_points' => [
            ['title' => 'Le terrain d’abord', 'body' => 'Des parcours mobiles clairs pour les fermes, sections, images et messages vocaux.'],
            ['title' => 'Toujours contextualisé', 'body' => 'Chaque signal reste relié à la ferme, la culture et la saison.'],
            ['title' => 'Action responsable', 'body' => 'État de révision, responsabilité et validation restent visibles.'],
        ],
        'field_image_alt' => 'Terres cultivées à Hawul, dans l’État de Borno', 'field_image_caption' => 'Terres cultivées · Hawul, État de Borno',
        'language_eyebrow' => 'La langue fait partie de l’accès', 'language_title' => 'La même histoire de terrain claire en anglais, haoussa et français.', 'language_body' => 'Les équipes peuvent présenter l’expérience dans la langue adaptée aux agriculteurs, partenaires et programmes régionaux—sans changer le sens du produit.',
        'language_cards' => [
            ['code' => 'EN', 'name' => 'English', 'sample' => 'See every field. Know what needs attention.'],
            ['code' => 'HA', 'name' => 'Hausa', 'sample' => 'Ka ga kowane fili. Ka san abin da ke bukatar kulawa.'],
            ['code' => 'FR', 'name' => 'Français', 'sample' => 'Voyez chaque parcelle. Sachez où agir.'],
        ],
        'faq_eyebrow' => 'Avant un déploiement', 'faq_title' => 'Des réponses claires avant de commencer.',
        'faqs' => [
            ['question' => 'Une ferme peut-elle contenir différentes cultures ?', 'answer' => 'Oui. Un agriculteur peut créer autant de sections que la surface le permet et attribuer une culture et une taille à chacune.'],
            ['question' => 'AgriShield fonctionne-t-il avec une connectivité limitée ?', 'answer' => 'L’expérience mobile est conçue pour le terrain et peut conserver les informations prises en charge. Les besoins de connexion dépendent du fournisseur de données et du parcours utilisé.'],
            ['question' => 'La plateforme invente-t-elle des conseils sans données ?', 'answer' => 'Non. La fraîcheur, l’état du fournisseur et la révision restent visibles. Les conseils dépendent des preuves et fournisseurs configurés pour le déploiement.'],
        ],
        'closing_label' => 'Commencez par un programme ciblé', 'closing_title' => 'Présentez-nous les fermes, cultures et décisions à accompagner.', 'closing_body' => 'Nous cartographierons avec votre équipe le parcours terrain, les sources de données, les besoins linguistiques et les prochaines actions responsables.', 'closing_cta' => 'Discuter d’un déploiement',
    ],
    'pages' => [
        'common' => ['cta_eyebrow' => 'Commencez par un parcours cultural', 'cta_title' => 'Dites-nous où l’appui terrain se bloque.', 'cta_body' => 'Présentez-nous une culture, une région ou un défi de conseil agricole.', 'cta_action' => 'Commencer la discussion'],
        'about' => [
            'meta_title' => 'À propos | AgriShield AI', 'meta_description' => 'Découvrez pourquoi AgriShield construit une intelligence agricole responsable.', 'eyebrow' => 'À propos d’AgriShield', 'title' => 'L’appui aux cultures a besoin d’une mémoire opérationnelle.', 'intro' => 'Nous construisons une intelligence agricole pratique pour les personnes chargées de comprendre le terrain et de mener la prochaine action à terme.',
            'statement_label' => 'Pourquoi ce produit', 'statement_title' => 'Le contexte terrain essentiel ne doit pas disparaître entre les messages.', 'statement_body' => ['Limites, sections culturales, signaux satellite, notes vocales et images vivent souvent dans des outils séparés—ou nulle part.', 'AgriShield les conserve dans un dossier de ferme autorisé pour voir ce qui a changé, ce qui a été examiné et la suite.'],
            'principles_label' => 'Discipline produit', 'principles_title' => 'Quatre règles guident le produit.', 'principles' => [
                ['code' => 'TERRAIN', 'title' => 'Le contexte d’abord', 'body' => 'Chaque signal reste relié à une ferme, une section et une saison connues.'],
                ['code' => 'PRIVÉ', 'title' => 'Dossiers protégés', 'body' => 'Les données de ferme, voix et images restent dans les parcours autorisés.'],
                ['code' => 'CLAIR', 'title' => 'Limites visibles', 'body' => 'État du fournisseur, fraîcheur et révision humaine restent visibles.'],
                ['code' => 'ACTION', 'title' => 'Suivi responsable', 'body' => 'Cas, conseils et validations gardent une trace opérationnelle utile.'],
            ],
            'team_label' => 'Le travail derrière AgriShield', 'team_title' => 'Opérations agricoles, logiciel sécurisé et déploiement rigoureux façonnent le travail.', 'team_body' => 'Le produit associe pratique terrain, ingénierie sécurisée et accompagnement de déploiement.',
        ],
        'solutions' => [
            'meta_title' => 'Plateforme | AgriShield AI', 'meta_description' => 'Découvrez satellite, NDVI, sections, diagnostic image, voix, humidité, météo et financement agricole.', 'eyebrow' => 'La plateforme AgriShield', 'title' => 'Gardez chaque signal agricole dans le même dossier opérationnel.', 'intro' => 'Une vue opérationnelle commune pour fermes, sections, télédétection, preuves terrain et suivi responsable.',
            'overview_label' => 'Intelligence agricole connectée', 'overview_title' => 'Voir le terrain, comprendre le signal, attribuer la suite.', 'overview_body' => 'Chaque capacité alimente le même dossier de ferme au lieu de créer un tableau de bord isolé.',
            'features' => [
                ['code' => '01 / CARTE', 'title' => 'Fermes et sections culturales', 'body' => 'Créez les sections nécessaires, affectez les cultures et suivez les surfaces.', 'items' => ['Limites validées', 'Cultures par section', 'Historique des saisons']],
                ['code' => '02 / OBSERVER', 'title' => 'Satellite et NDVI', 'body' => 'Consultez les signaux de végétation par ferme, section et date.', 'items' => ['Signaux NDVI', 'Dates d’observation', 'État du fournisseur']],
                ['code' => '03 / SOL', 'title' => 'Humidité du sol et météo', 'body' => 'Lisez sol et prévisions à côté du plan cultural actif.', 'items' => ['Humidité', 'Indicateurs du sol', 'Risque de pluie']],
                ['code' => '04 / SIGNALER', 'title' => 'Diagnostic image et Voix du terrain', 'body' => 'Capturez photos ou notes vocales et gardez-les reliées à la bonne ferme.', 'items' => ['Preuves privées', 'Langues prises en charge', 'État de révision']],
                ['code' => '05 / AGIR', 'title' => 'Conseils et suivi', 'body' => 'Publiez une action pratique et conservez responsable, lecture et validation.', 'items' => ['Priorité et délai', 'Responsable', 'Historique de validation']],
                ['code' => '06 / ACCÈS', 'title' => 'Prêts agricoles et services', 'body' => 'Explorez les produits configurés et soumettez une demande liée à la ferme.', 'items' => ['Produits éligibles', 'Demande liée à la ferme', 'État de la demande']],
            ],
            'provider_label' => 'Conçu autour des fournisseurs', 'provider_title' => 'Les services spécialisés se connectent sans masquer leur source.', 'provider_body' => 'Satellite, langues, analyse des cultures, météo et finance affichent leur disponibilité. Le dossier agricole reste utile même si un service externe est indisponible.',
        ],
        'impact' => [
            'meta_title' => 'Fonctionnement | AgriShield AI', 'meta_description' => 'De l’enregistrement de la ferme à la preuve, la révision, le conseil et la validation.', 'eyebrow' => 'Parcours produit', 'title' => 'Du dossier de ferme à l’action validée.', 'intro' => 'Un parcours clair réunit les étapes essentielles sans masquer les données manquantes ni l’état de révision.',
            'steps' => [
                ['number' => '01', 'label' => 'Établir le contexte', 'title' => 'Enregistrer la ferme, les sections et la saison', 'body' => 'Associez chaque signal à une organisation, un responsable, une limite et un plan cultural.', 'note' => 'Fermes · sections · saisons'],
                ['number' => '02', 'label' => 'Suivre le changement', 'title' => 'Réunir satellite, sol et météo', 'body' => 'Comparez les observations avec la culture et la section qu’elles décrivent.', 'note' => 'NDVI · humidité · météo'],
                ['number' => '03', 'label' => 'Conserver la preuve', 'title' => 'Transformer un signalement en cas révisable', 'body' => 'Gardez voix, images, résultats et confiance dans l’espace autorisé.', 'note' => 'Voix du terrain · diagnostic image'],
                ['number' => '04', 'label' => 'Boucler le suivi', 'title' => 'Publier une action et enregistrer la validation', 'body' => 'Conservez l’historique du conseil, son responsable et sa validation.', 'note' => 'Conseils · suivi'],
            ],
        ],
        'partners' => [
            'meta_title' => 'Collaborer avec AgriShield | AgriShield AI', 'meta_description' => 'Planifiez un déploiement AgriShield ciblé.', 'eyebrow' => 'Collaborer avec nous', 'title' => 'Commencez par un parcours terrain que votre équipe maîtrise déjà.', 'intro' => 'Nous travaillons au mieux avec des organisations disposant d’une communauté agricole, d’une équipe terrain et d’une responsabilité de suivi.',
            'starting_label' => 'Bons points de départ', 'starting_title' => 'Commencez par un manque opérationnel.', 'starting_body' => 'Un déploiement ciblé clarifie responsabilités, sources et mesures utiles.', 'starting_points' => ['Créer un registre fiable des fermes et sections', 'Suivre des signaux satellite, sol ou météo', 'Orienter voix et images vers une équipe responsable', 'Relier les agriculteurs éligibles à des partenaires financiers', 'Publier des conseils et enregistrer leur validation'],
            'proof_label' => 'Rôles de livraison clairs', 'proof_title' => 'Chaque déploiement commence par des responsabilités nommées.', 'proof_body' => 'Avant le terrain, l’équipe définit le périmètre, les sources de données, les fournisseurs, les rôles de révision et le chemin d’escalade.',
        ],
        'team' => [
            'meta_title' => 'Équipe | AgriShield AI', 'meta_description' => 'Les disciplines derrière AgriShield.', 'eyebrow' => 'Équipe', 'title' => 'Expertises agricoles, données et terrain réunies.', 'intro' => 'AgriShield réunit des personnes issues des programmes agricoles, des données géospatiales, de l’ingénierie produit et de la mise en œuvre terrain.',
            'disciplines_label' => 'Disciplines actuelles', 'disciplines_title' => 'Les capacités nécessaires pour livrer le produit.', 'disciplines' => [
                ['code' => '01', 'title' => 'Conception de programmes agricoles', 'body' => 'Traduit les responsabilités terrain en parcours de service concret.'],
                ['code' => '02', 'title' => 'Systèmes géospatiaux et données', 'body' => 'Relie limites, observations satellite et état des fournisseurs.'],
                ['code' => '03', 'title' => 'Ingénierie produit', 'body' => 'Assure API, expérience mobile, espace web et contrôles opérationnels.'],
                ['code' => '04', 'title' => 'Mise en œuvre terrain', 'body' => 'Accompagne intégration, dossiers, langues et adoption responsable.'],
            ],
        ],
        'contact' => [
            'meta_title' => 'Contact | AgriShield AI Ltd', 'meta_description' => 'Contactez AgriShield pour un déploiement d’intelligence agricole.', 'eyebrow' => 'Contact', 'title' => 'Présentez-nous le problème d’appui aux cultures.', 'intro' => 'Expliquez où les agriculteurs ou conseillers sont bloqués. Nous vous aiderons à définir un premier déploiement ciblé.',
            'form_label' => 'Commencer la discussion', 'form_title' => 'Nous sommes prêts à écouter.', 'form_body' => 'Pour le cadrage, une démonstration, un partenariat ou une demande institutionnelle, contactez notre équipe.', 'email' => 'E-mail', 'region' => 'Zone prioritaire', 'region_value' => 'Nord du Nigeria', 'name' => 'Votre nom', 'name_placeholder' => 'Nom complet', 'work_email' => 'E-mail professionnel', 'organisation' => 'Organisation', 'organisation_placeholder' => 'Nom de l’organisation', 'interest' => 'Je souhaite', 'message' => 'Comment pouvons-nous aider ?', 'message_placeholder' => 'Décrivez les fermes, utilisateurs et résultats recherchés.', 'options' => ['Demander une démonstration', 'Planifier un déploiement', 'Explorer un partenariat données ou finance', 'Question générale'], 'submit' => 'Préparer l’e-mail', 'privacy' => 'Cela ouvre votre application e-mail. Aucune donnée n’est stockée sur ce site.'
        ],
        'field_voice' => [
            'meta_title' => 'Voix du terrain | AgriShield AI', 'meta_description' => 'Capturez les questions culturales par la voix avec leur contexte agricole.', 'eyebrow' => 'Voix du terrain / réception prête', 'title' => 'Capturez la question. Gardez le contexte.', 'intro' => 'Enregistrez ou importez la question d’un agriculteur, reliez-la à une ferme et conservez un historique privé.',
            'workflow_label' => 'Parcours disponible', 'workflow_title' => 'Conçu pour un téléphone sur le terrain.', 'steps' => [
                ['title' => 'Enregistrer ou importer', 'body' => 'Capturez une courte note vocale ou utilisez un fichier existant.'],
                ['title' => 'Joindre le contexte agricole', 'body' => 'Reliez la question à une ferme autorisée et aux langues sélectionnées.'],
                ['title' => 'Garder le cas privé', 'body' => 'Audio et historique protégés restent dans l’espace de l’organisation.'],
                ['title' => 'Orienter pour révision', 'body' => 'Donnez à l’équipe terrain un endroit clair pour examiner et poursuivre.'],
            ],
            'panel_badge' => 'Espace organisation sécurisé', 'panel_title' => 'La réception vocale est disponible', 'panel_body' => 'Transcription, traduction et conseil généré s’activent lorsqu’un fournisseur vocal de production est configuré.', 'panel_action' => 'Ouvrir Voix du terrain', 'panel_signin' => 'Se connecter pour continuer', 'bounds_label' => 'Limites publiées', 'bounds_title' => 'Ce que fait la fonction—et ce qui dépend d’un fournisseur.', 'core_title' => 'Plateforme principale', 'core_items' => ['Réception et lecture audio privées', 'Sélection de ferme et de langue', 'Historique de l’organisation', 'Visibilité des états et échecs'], 'provider_title' => 'Fournisseur configuré', 'provider_items' => ['Transcription vocale', 'Traduction', 'Conseil cultural généré', 'Signaux d’escalade automatisés'],
        ],
    ],
    'footer' => ['eyebrow' => 'AgriShield / Nord du Nigeria', 'title' => 'Un seul dossier, de la question terrain jusqu’au suivi.', 'body' => 'Des dossiers culturaux sécurisés, les questions des agriculteurs et des conseils traçables pour les équipes terrain.', 'product' => 'Produit', 'company' => 'Entreprise', 'about' => 'À propos', 'team' => 'Équipe', 'work' => 'Travailler avec nous', 'contact' => 'Contact', 'disclaimer' => 'Les conseils culturaux doivent être examinés par des professionnels locaux qualifiés.'],
];

# Catalogue des règles de validation spatiale

Référence de conception de la [story #8](https://github.com/longitude-one/spatial-validator/issues/8),
liée à l’[Epic #7](https://github.com/longitude-one/spatial-validator/issues/7).
Rédaction française du 21 octobre 2026. Décisions approuvées et précisions restantes sont distinguées ci-dessous.
Ce fichier ne modifie aucun contrat, algorithme, diagnostic ou comportement d’exécution.

## 1. Périmètre et méthode

Le catalogue sépare quatre catégories :

| Catégorie | Question évaluée | Conséquence |
| --- | --- | --- |
| Validité | L’objet respecte-t-il la définition de son type ? | Composer les exigences du type et des composants. |
| Simplicité | Existe-t-il des intersections ou tangences interdites ? | Ne pas l’imposer à toute courbe valide. |
| Fermeture | Les extrémités coïncident-elles dans l’espace choisi ? | Une courbe ouverte peut être valide. |
| Restriction métier | L’application impose-t-elle davantage ? | Contrainte explicite, jamais exigence ISO implicite. |

La fermeture topologique d’un ensemble inclut sa frontière ; elle ne signifie pas qu’une courbe
revient à son point initial ni qu’une surface polyédrique enferme un solide.
Les règles sont des exigences de conception, pas des noms de futures classes PHP ni des codes de violation.
Les scénarios décrivent des candidats abstraits ; certains ne sont pas constructibles avec le modèle installé.

### Révisions de l’inventaire

| Élément | Révision inspectée |
| --- | --- |
| `spatial-validator` | `fca0ef88424a862deda7f7d2e8d7c8deafe58c16` |
| `spatial-types` installé, `1.0.0-beta` | `ceaee5d142c77fc3922ab50c762fa6648fea0e30` |
| `spatial-core` installé, `1.3.0` | `1289582288e8954243de5ccb1f28ab7e78f4e9a4` |

Les références relatives à `lib/` et `tests/` se rapportent à la première révision.
Les références `vendor/longitude-one/` désignent les deux révisions installées ci-dessus ;
`composer.lock` local ne constitue pas un contrat de version publié.
Le modèle installé comporte encore des assertions historiques : ses garanties ne préjugent pas
les garanties après leur retrait dans l’Epic d’extraction.
Une implémentation tierce de `SpatialInterface` ne bénéficie pas de ces garanties.

## 2. Sources et niveau d’autorité

| Code | Document et statut | Clauses inspectées et usage |
| --- | --- | --- |
| ISO-CD | ISO/IEC CD 13249-3:201x(E), 2009-01-16, Committee Draft | Source primaire disponible ; non finale. |
| ISO-CD/base | Même document | 4.2.1, 4.2.2, 5.1.7–5.1.10 : dimensions, égalité, états. |
| ISO-CD/courbes | Même document | 4.2.3–4.2.7, 7.1.6–7.1.9, 7.2.1, 7.3.1, 7.4.1. |
| ISO-CD/surfaces | Même document | 4.2.8–12 ; 8.3.1, 8.4.1, 8.5.1. |
| ISO-CD/collections | Même document | 4.2.14–4.2.19 : membres, simplicité et relations. |
| OGC | OGC 06-103r4, SFA 1.2.1, Corrigendum | 6.1.6–6.1.7, 6.1.11–6.1.14 : comparaison des définitions. |
| OGC/GML | OGC 07-036, GML 3.2.1, 2007-08-27, standard final | 10.5.8 : anneau linéaire, quatre positions minimum. |
| ISO 19107 | Référence normative d’ISO-CD, clause 2 | Schéma spatial ; texte source non inspecté ici. |
| ISO 19111 | Référence normative d’ISO-CD, clause 2 | Systèmes de référence ; texte source non inspecté ici. |
| ISO 19136 | Référence normative d’ISO-CD, clause 2 | GML ; pas de validation de syntaxe GML dans cet Epic. |

ISO-CD a été lu dans le PDF privé fourni avec la compétence `longitudeone-spatial-standards`.
OGC a été lu dans sa transcription locale ; le [document public OGC](https://portal.ogc.org/files/?artifact_id=25355)
permet de retrouver l’original. Les extraits privés ne sont pas redistribués.
Les éditions et clauses détaillées des trois références ISO 191xx restent à vérifier avant
une conclusion qui dépendrait exclusivement de ces textes. Leur absence ne transforme pas une hypothèse en exigence.

Dans la suite : **N** = exigence ou définition relevée dans la source citée ; **H** = historique observé ;
**L** = décision LongitudeOne explicitement reçue ; **P** = proposition à approuver.
Les notes, exemples et annexes informatives ne sont pas utilisés comme obligations autonomes.
Aucune conformité générale SQL/MM n’est revendiquée. Le comportement d’une base de données n’est pas une source
normative.

## 3. Matrice des types et des capacités

### Variantes réellement disponibles

`G` = Geometry ; `A` = Geography. `Oui` signifie classe disponible, pas validation complète.

| Type | G XY | G XYZ | G XYM | G XYZM | A XY | A XYZ | A XYM | A XYZM |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Point | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| LineString | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| CircularString | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| CompoundCurve | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| Polygon | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| Triangle | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| MultiPoint | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| MultiLineString | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| MultiPolygon | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| GeometryCollection / GeographyCollection | Oui | Oui | Oui | Oui | Oui | Oui | Oui | Oui |
| PolyhedralSurface | Absente | Oui | Absente | Oui | Absente | Oui | Absente | Oui |

Sources : `vendor/longitude-one/spatial-types/lib/Types/Dimension{2,3z,3m,4zm}/`
et `Implementation/SpatialTypeImplementationStatus.php` dans la même bibliothèque.
TIN, CurvePolygon, MultiCurve et MultiSurface ne sont pas implémentés : ils restent hors périmètre.
Une dimension de coordonnées de trois ne permet pas de confondre XYZ et XYM.

### Applicabilité des règles

Les identifiants sont définis en section 4. Les règles d’un composant s’appliquent aussi lorsque celui-ci
est contenu dans un autre objet. Une règle de simplicité n’est obligatoire pour la validité que si
la définition du type l’exige, notamment pour les anneaux des surfaces.

| Type | Règles communes | Règles propres | Composants à parcourir |
| --- | --- | --- | --- |
| Point | V01, V02 | V03, V16 si Geography | Aucun |
| LineString | V01, V02, C01–C04 | V04, B01 ; S01, F01 sur demande | Points |
| CircularString | V01, V02, C01–C04 | V05, B01 ; S01, F01 sur demande | Points et arcs |
| CompoundCurve | V01, V02, C01–C04 | V06 ; S01, F01 sur demande | Courbes et jonctions |
| Polygon | V01, V02, C01–C04 | V07–V09, V15 | Anneau extérieur et trous |
| Triangle | Règles de Polygon | V10 | Anneau extérieur |
| MultiPoint | V01, V02, C01–C04 | S02 sur demande | Points |
| MultiLineString | V01, V02, C01–C04 | S03, F02 sur demande | Lignes |
| MultiPolygon | V01, V02, C01–C04 | V11 | Polygones et leurs anneaux |
| Collection spatiale | V01, V02, C01–C04 | Recouvrement non interdit par défaut | Membres |
| PolyhedralSurface | V01, V02, C01–C04 | V12–V14 | Faces et leurs anneaux |

V16 s’applique aussi à chaque Point Geography contenu dans un objet composite.
Cette table se combine avec les deux suivantes : aucune cellule ne promet un algorithme géodésique.

| Classe de contrôle | Geometry | Geography |
| --- | --- | --- |
| Type, famille, dimension, référence, cardinalité | Applicable | Applicable |
| Absence, EMPTY et récursion | Applicable | Applicable |
| Jonctions et fermeture | XYZ si Z, sinon XY ; M ignoré | Sémantique différée, Q03 |
| Topologie et planéité | Cartésien ; espace ci-dessous | Hors couverture initiale, Q03 |
| Limites propres au SRS | Pas de contrôle général | Pas de contrôle propre à chaque SRS |
| Bornes angulaires en degrés | Non applicables | Latitude [−90, +90], longitude [−180, +180] ; Q04 |

**L-GEO-01 — décision du 21 octobre 2026 :** Alexandre demande les contrôles structurels d’abord,
avec une topologie géographique à spécifier séparément. Cela ne valide aucun calcul cartésien comme
substitut géodésique. Les contrôles indisponibles produisent un avertissement selon Q03.

| Opération | XY | XYZ | XYM | XYZM | M |
| --- | --- | --- | --- | --- | --- |
| Simplicité et fermeture ordinaires ISO-CD | XY | XY | XY | XY | Pas une dimension spatiale |
| Simplicité et fermeture `3D` ISO-CD | Sans apport Z | XYZ | Sans apport Z | XYZ | Ignoré spatialement |
| Plan d’une face de PolyhedralSurface | Type absent | XYZ | Type absent | XYZ | Ignoré par l’historique |
| Plan/topologie Polygon | XY | XYZ | XY | XYZ | Pas une dimension spatiale |
| Compatibilité de dimension | XY | XYZ | XYM | XYZM | Distinguer sa présence |

ISO-CD 5.1.8–5.1.9 et 7.1.6–7.1.9 séparent opérations ordinaires et 3D.
**L-DIM-01 — décision du 21 octobre 2026 :** Alexandre demande au futur validateur générique Geometry
d’utiliser automatiquement XYZ lorsque Z est présent, sinon XY. XYM est donc évalué spatialement en XY,
et XYZM en XYZ. M n’ajoute pas de dimension spatiale.
Ce choix LongitudeOne ne redéfinit pas les opérations ordinaires ISO-CD et ne change pas les contraintes existantes.
Les bornes numériques, epsilon et projections ne sont pas déduits de cette décision.
Les calculs géométriques ignorent toujours M (Q01), contrairement à `equalsTo` historique qui compare aussi M.

## 4. Catalogue stable des règles

Chaque fiche indique catégorie, condition, source, couverture et scénarios de référence.
Les verdicts des scénarios portent sur la règle citée, pas sur la validité globale de l’objet.
Les nombres sont exacts et finis sauf mention contraire. Chaque scénario se décline dans les variantes
applicables de la matrice ; pour Geography, seuls les contrôles structurels ont un verdict initial.

### V01 — Validité 01 — Un objet EMPTY est valide ; `null` n’est pas un objet spatial

Une géométrie peut être vide et valide. Cela ne permet pas à ses composants d’être invalides ou EMPTY
lorsque la définition du type l’interdit.

| Cas | Résultat attendu |
| --- | --- |
| Point dont toutes les ordinates de la variante sont nulles | Point EMPTY valide selon V03. |
| LineString sans aucun point | LineString EMPTY valide. |
| LineString avec un seul point non vide | Invalide : au moins deux points hors EMPTY. |
| LineString contenant un Point invalide | Invalide : chaque Point doit être valide. |
| LineString contenant un Point EMPTY | Invalide : ses points doivent fournir des positions. |
| LineString avec au moins deux Points valides et non vides | Poursuivre les autres contrôles de la ligne. |
| Valeur PHP `null` ou `''` | Ignorée par la contrainte spatiale, selon L-INPUT-01. |

- **Règle :** accepter un véritable objet EMPTY ; pour un objet contenant des composants,
  vérifier ces composants et les règles propres au type. Ne pas déclarer une ligne valide sur son seul nombre de points.
- **Sources :** ISO-CD 5.1.10(2a) accepte EMPTY ; 7.2.1 Description 4 et 11 fixe la cardinalité des lignes ;
  7.2.2 utilise `ST_Points` (7.2.3), qui appelle `ST_CheckConsecDups` (16.1.7), puis `ST_CheckNulls`
  (16.1.6). Cette dernière procédure rejette explicitement les membres null et EMPTY.
- **Couverture : partielle.** Le modèle installé refuse déjà un Point EMPTY dans une LineString.
  Le validateur générique et la validation récursive restent à construire dans C/D/F.
  La fermeture et la définition d’un anneau sont traitées séparément dans F01.

#### L-INPUT-01 — Décision sur les entrées 01 — Suivre les conventions Symfony

Décision d’Alexandre du 21 octobre 2026, précisée dans la [story #10][story10].
Tous les validateurs de contraintes suivent ce contrat :

| Cas | Comportement |
| --- | --- |
| Mauvaise contrainte fournie au validateur | Lever `UnexpectedTypeException` de Symfony. |
| Valeur PHP `null` ou `''` | Retourner sans violation ; utiliser `NotNull` ou `NotBlank` pour exiger une valeur. |
| Valeur incompatible avec l’interface spatiale attendue | Lever `UnexpectedValueException` de Symfony. |
| Objet spatial du type attendu, y compris EMPTY | Appliquer les règles géométriques correspondantes. |

Le service Symfony Validator transforme `UnexpectedValueException` en violation de type.
Un appel direct au validateur de contrainte laisse sortir cette exception.
Aucune exception dédiée à ces entrées n’est ajoutée par la bibliothèque.

Référence : [documentation officielle Symfony][symfony-input].
La story #10 applique ce contrat aux validateurs de contrainte existants.

[story10]: https://github.com/longitude-one/spatial-validator/issues/10
[symfony-input]: https://symfony.com/doc/7.4/validation/custom_constraint.html#creating-the-validator-itself

### V02 — Validité 02 — Coordonner finitude et précision

- **Catégorie :** précondition numérique, points de tous objets non vides.
- **Décision LongitudeOne (Q04) :** refuser `INF`, `-INF` et `NAN` dans X/Y et dans Z/M présents.
  Produire une violation pour ces valeurs ; ne pas les assimiler à `null` ou EMPTY.
  Le contrôle de M est structurel, même si les calculs géométriques ignorent M.
- **H / couverture : partiellement couverte.** `Internal/PlanarPatch::isValid` refuse XYZ non finis.
  Aucun contrôle général équivalent ni contrôle de M ; pas d’epsilon dans les algorithmes inspectés.
- **Scénarios :** coordonnées finies → passent ce contrôle ; X infini, Z NaN ou M infini → violation.
  Un patch avec X infini est déjà rejeté par le code actuel.
- **Stories concernées :** E, F–O ; Q02, Q04. Aucun résultat arithmétique non fiable ne prouve la validité.

### V16 — Validité 16 — Bornes angulaires des points Geography

- **Catégorie :** validité des coordonnées, Point Geography non vide et points de ses objets composites.
- **Décision LongitudeOne (Q04) :** longitude X entre −180 et +180 degrés et latitude Y entre −90 et +90 degrés,
  bornes incluses. Un dépassement produit une violation ; aucune limite propre à un SRID n’est ajoutée.
- **Contrôles déjà présents : partiellement couverte.** Le modèle applique ces bornes dans
  `vendor/longitude-one/spatial-types/lib/Types/AbstractPoint.php`, méthodes `initializeLatitude`
  et `initializeLongitude`, à la révision indiquée en section 1. Pas de contrainte dédiée ni de test
  de ces bornes dans `spatial-validator` à la révision inventoriée. Cet inventaire n’impose aucune compatibilité.
- **Scénarios :** longitudes −180 et +180, latitudes −90 et +90 → admises ;
  longitude −180.000001 ou +180.000001 → violation ; latitude −90.000001 ou +90.000001 → violation ;
  Point Geography EMPTY → V03 ; Point Geometry X=700000 → aucune violation de V16.
  Décliner en XY, XYZ, XYM et XYZM ; Z/M ne changent pas ces bornes.
- **Stories concernées :** E et D pour le parcours des points contenus.

### V03 — Validité 03 — Point : toutes les ordinates renseignées ou toutes nulles

- **Catégorie :** validité structurelle, Point Geometry ou Geography, vide ou non vide.
- **N :** ISO-CD 4.2.3 décrit une position avec X/Y et éventuellement Z/M ; le point est simple.
  La représentation PHP par ordinates nulles relève du modèle LongitudeOne, pas de cette clause ISO.
- **H / L-POINT-01 :** règle du modèle confirmée par Alexandre le 21 octobre 2026 : pour les ordinates
  de la variante déclarée, toutes nulles → Point EMPTY ; aucune nulle → Point non vide ;
  mélange d’ordinates nulles et renseignées → invalide. La présence de M dans XYM/XYZM fait partie
  de ce contrôle structurel, même si M ne participe pas aux calculs spatiaux.
- **H / couverture : partiellement couverte.** `AbstractPoint::hasOnlyNullCoordinates` et les constructeurs
  des variantes appliquent déjà cette règle, avec `InvalidValueException` pour un état partiel.
  Aucun validateur Point spécialisé dans `spatial-validator` ; il devra contrôler les candidats d’interface
  sans supposer que ces garanties de construction leur sont applicables.
- **Scénarios :** voir la table ; un Point non vide doit aussi passer les autres contrôles applicables,
  notamment ceux de coordonnées. La cohérence de `isEmpty()` avec les ordinates doit être vérifiée
  pour un candidat d’interface ; un état partiel ne devient pas valide parce qu’il se déclare EMPTY.
- **Stories concernées :** E ; V01 pour EMPTY et C03 pour la variante déclarée.

| Variante | Ordinates à contrôler | Toutes nulles | Aucune nulle | Mélange |
| --- | --- | --- | --- | --- |
| XY | X, Y | EMPTY valide | Non vide | Invalide |
| XYZ | X, Y, Z | EMPTY valide | Non vide | Invalide |
| XYM | X, Y, M | EMPTY valide | Non vide | Invalide |
| XYZM | X, Y, Z, M | EMPTY valide | Non vide | Invalide |

Exemples : XY `(null,null)` → EMPTY ; XY `(1,null)` → invalide ; XYZ `(1,2,null)` → invalide ;
XYM `(1,2,null)` → invalide ; XYZM `(null,null,null,null)` → EMPTY.
Les axes absents de la variante ne sont pas contrôlés : `getZ() === null` pour un Point XY non vide
ne le rend pas invalide. Le nombre zéro est une coordonnée renseignée, distincte de `null`.

### V04 — Validité 04 — Cardinalité d’une LineString

- **Catégorie :** validité, liste non vide de points.
- **N :** ISO-CD 7.2.1, Description 3–5 et 10–11 : pas de membre null, au moins deux points
  pour être bien formée ; zéro point représente EMPTY.
  Les clauses 7.2.2–7.2.3 et les appels 16.1.7 → 16.1.6 interdisent les composants Point EMPTY ;
  chaque Point doit aussi satisfaire V03 et les autres règles applicables.
  La même chaîne contrôle les doublons consécutifs : voir B01.
- **H / couverture : partiellement couverte.** `MinimumPointCount(limit: 2)` permet le seuil ;
  sa valeur par défaut est 4. Pas de composition générale LineString ni d’exemption EMPTY.
- **Scénarios :** zéro → V01 ; un point → invalide ; deux points distincts → cardinalité valide ;
  ligne qui se croise → peut satisfaire V04 tout en échouant S01 ;
  deux points dont un EMPTY → invalide ; deux Points EMPTY → invalide, pas une LineString EMPTY ;
  Point à ordinates partiellement nulles → invalide même si le nombre de membres est suffisant.
- **Stories concernées :** F ; B01 pour les doublons consécutifs et Q06 pour la composition historique des anneaux.

### V05 — Validité 05 — Structure et interprétation d’une CircularString

- **Catégorie :** validité, points et segments d’arc non vides.
- **N :** ISO-CD 7.3.1, Description 6–11 et 4.2.6 : un premier arc à trois points,
  puis deux points par arc ; nombre impair au moins trois hors EMPTY.
  Trois points colinéaires produisent un segment rectiligne dégénéré, pas un rejet automatique.
  Le point intermédiaire doit être distinct du début et de la fin de son arc.
  Le cercle complet et son point intermédiaire suivent le cas particulier de la source.
- **H / couverture : manquante dans le validateur.** Le modèle impose déjà cardinalité et points non vides,
  ainsi que des points adjacents différents par comparaison de `toArray()` avec `==` ;
  cette comparaison inclut les ordinates présentes et n’établit pas une égalité spatiale ignorant M.
- **Scénarios :** 3 puis 5 points → structure admissible ; 2 ou 4 → invalide ;
  `(0,0),(1,0),(2,0)` → dégénérescence rectiligne admise par la définition ;
  `(1,0),(-1,0),(1,0)` → cas cercle complet, ne pas traiter comme trois points distincts obligatoires ;
  A,A,B et A,B,B avec A≠B → invalides, point intermédiaire confondu avec une extrémité.
  Même position spatiale mais M différent → même position ; M ne distingue pas les points de l’arc.
- **Stories concernées :** H ; simplicité des arcs à spécifier séparément, pas par les seules cordes ; Q01–Q03.

### V06 — Validité 06 — Composants et continuité d’une CompoundCurve

- **Catégorie :** validité, courbes contributrices et jonctions.
- **N :** ISO-CD 7.4.1, Description 3–10 : LineString ou CircularString bien formées,
  contiguës, extrémité précédente coïncidant avec début suivant ; zéro composant représente EMPTY.
- **H / couverture : manquante dans le validateur.** Le modèle contrôle types, absence de membres EMPTY,
  compatibilité et continuité par `equalsTo` ; cette égalité ne définit pas le futur contrat spatial.
- **Scénarios :** ligne A→B puis arc B→C → jonction admissible ; A→B puis C→D, B≠C → rejet ;
  Polygon membre → rejet de type ; jonction même position XY/XYZ mais M différent → jonction admise ;
  membre EMPTY → rejet dans le parcours ISO-CD (Q05).
- **Stories concernées :** I, dépend F/H et D ; Q01, Q05.

### S01 — Simplicité 01 — Simplicité des courbes

- **Catégorie :** simplicité, courbe non vide et espace annoncé ; requise pour les anneaux.
- **N :** ISO-CD 4.2.4 et 5.1.8–5.1.9 : pas de point intérieur rencontré à nouveau
  dans l’intérieur ou sur la frontière. OGC 6.1.6–6.1.7 confirme la distinction courbe/anneau.
- **H / couverture : partiellement couverte.** `SimpleLineStringValidator` en XY ;
  `Simple3dLineStringValidator` en XYZ ; aucune simplicité des arcs ou de toute une CompoundCurve.
- **Scénarios :** chemin ouvert sans croisement → simple ; nœud papillon → non simple ;
  recouvrement de segments adjacents → non simple ; simple boucle fermée → simple ;
  croisement XY à altitudes différentes → non simple en XY, simple en XYZ si aucun autre contact ;
  modifier seulement M → résultat spatial inchangé.
- **Stories concernées :** F–I, K–O ; Q01 résolue pour les ordinates ; Q02 pour la robustesse.

### F01 — Fermeture 01 — Fermeture et anneau

- **Catégorie :** fermeture puis composition avec simplicité, courbes et anneaux de surface.
- **N :** ISO-CD 4.2.4–4.2.5 et 7.1.6–7.1.9 : fermé si extrémités égales ;
  anneau si simple et fermé ; EMPTY n’est ni fermé ni anneau.
- **H / couverture : divergente pour la définition complète d’un anneau.**
  `FirstPointEqualsLastPointValidator` utilise `equalsTo` ; `RingValidator` ajoute minimum 4 et absence
  de doublons consécutifs, sans simplicité. Le minimum de quatre positions pour un anneau linéaire
  est explicite dans OGC 07-036, GML 3.2.1, 10.5.8 ; voir Q06.
- **Scénarios :** A,B,C,A non colinéaires → anneau ; A,B,C,D,A croisé → fermé mais pas anneau ;
  départ/arrivée mêmes XY, Z différent → non fermé pour une variante avec Z ; M différent → sans effet ;
  ligne EMPTY → non anneau ; moins de quatre positions → pas d’anneau linéaire valide (Q06).
- **Stories concernées :** G, J–L, O ; Q01, Q06 et compatibilité Q07.

### V07 — Validité 07 — Frontières linéaires des Polygon

- **Catégorie :** validité, Polygon non vide, extérieur et tous trous.
- **N :** ISO-CD 8.3.1, Description 2–4 : extérieur et intérieurs sont des anneaux linéaires.
- **H / couverture : partiellement couverte.** Pas de contrainte Polygon ; Ring existe et est incomplet.
  L’acceptation par le constructeur installé ne démontre pas la simplicité.
  Chaque anneau linéaire non vide exige au moins quatre positions, fermeture comprise (Q06).
- **Scénarios :** extérieur fermé simple → admissible ; extérieur ouvert ou trou croisé → invalide ;
  absence d’extérieur dans un objet annoncé non vide → invalide structurel ; extérieur EMPTY avec trous → invalide (Q05).
- **Stories concernées :** J, dépend G/D ; F01, V01 et Q05.

### V08 — Validité 08 — Planéité et non-dégénérescence des surfaces planes

- **Catégorie :** validité, Polygon/Triangle non vides et faces de PolyhedralSurface.
- **N :** ISO-CD 4.2.9–4.2.10 : surface plane ; OGC 6.1.11.1 : Polygon plan.
  Ne pas déduire la planéité d’une face XYZ de sa seule projection XY.
- **H / couverture : partiellement couverte.** `PlanarPatch` vérifie un plan non dégénéré en XYZ
  uniquement lors de la validation des surfaces polyédriques ; comparaison exacte, tous anneaux inclus.
- **Scénarios :** carré dans un plan incliné ou vertical → plan ; quatre sommets non coplanaires → rejet ;
  extérieur entièrement colinéaire → pas de surface plane non dégénérée ; trou hors du plan → rejet.
- **Stories concernées :** J, L, O ; Q01/Q02, Geography différée selon L-GEO-01.

### V09 — Validité 09 — Relations entre anneaux d’un Polygon

- **Catégorie :** validité topologique, extérieur et trous d’un Polygon non vide.
- **N :** ISO-CD 4.2.9 : pas de croisement entre anneaux, contacts ponctuels possibles,
  pas de coupures, pointes ni perforations, intérieur connexe, chaque trou délimite une composante extérieure.
  OGC 6.1.11.1 précise les contacts tangents ; la note explicative n’ajoute pas une règle indépendante.
- **H / couverture : manquante.** Ni Ring ni PlanarPatch ne vérifie ces relations.
- **Scénarios :** trou strictement intérieur → admissible ; trou extérieur, trou traversant la coque,
  trous qui se recouvrent ou trou imbriqué créant un îlot → rejet comme Polygon unique ;
  contact tangent isolé sans déconnexion intérieure → admissible ; segment partagé ou chaîne de trous
  déconnectant l’intérieur → rejet. Un contact ponctuel ne suffit pas à conclure valide.
- **Stories concernées :** K, dépend J ; Q01–Q03 pour espace, précision et Geography.

### V15 — Validité 15 — Orientation relative des anneaux de Polygon

- **Catégorie :** validité / convention d’orientation, extérieur et trous d’un Polygon non vide.
- **N OGC :** 6.1.11.1, deuxième paragraphe : le parcours extérieur définit le côté supérieur,
  les anneaux intérieurs ont le sens opposé vu de ce même côté. Cela n’impose pas un sens
  horaire absolu sur tous les CRS. L’équivalence avec une obligation ISO-CD n’est pas établie ici.
- **H / couverture : manquante** pour l’orientation relative dans `spatial-validator`.
  V13 vérifie une autre orientation : celle de frontières partagées entre faces.
- **Scénarios :** extérieur antihoraire et trou horaire dans le plan XY → convention OGC satisfaite ;
  trou de même orientation que l’extérieur → avertissement selon Q09 ; inversion simultanée
  de tous les anneaux → orientation relative conservée. Face verticale XYZ → examiner dans son plan.
- **Décision LongitudeOne :** signaler ce défaut d’orientation par un avertissement dans le payload (Q09).
- **Stories concernées :** J/K/O/P ; vérifier chaque trou par rapport à l’extérieur.

### V10 — Validité 10 — Triangle

- **Catégorie :** validité, Triangle non vide, extérieur seulement.
- **N :** ISO-CD 8.4.1, Description 2–4 : anneau de quatre points, aucun trou.
  OGC 6.1.11.1 exige explicitement trois sommets distincts non colinéaires.
  La non-dégénérescence ne se réduit pas à compter les quatre positions avec répétition de fermeture.
- **H / couverture : partiellement couverte.** `TriangleValidator` contrôle quatre points, aucun trou,
  délègue à Ring ; aucun contrôle explicite de non-colinéarité.
- **Scénarios :** `(0,0),(1,0),(0,1),(0,0)` → valide ; trois sommets colinéaires → invalide ;
  quatrième sommet distinct → invalide ; trou → invalide ; EMPTY → V01.
- **Stories concernées :** L, dépend G/J ; décliner le cas vertical XYZ selon Q01.

### S02 — Simplicité 02 — Simplicité d’un MultiPoint

- **Catégorie :** simplicité, membres Point.
- **N :** ISO-CD 4.2.15 : simple si aucun couple de points égaux ; aucune notion d’ordre ni de connexion.
- **H / couverture : manquante.** L’absence de doublons consécutifs d’une ligne n’est pas un contrôle MultiPoint.
- **Scénarios :** deux positions distinctes → simple ; même position deux fois → non simple,
  sans déduire automatiquement une invalidité ; même XY et Z distinct → dépend de l’opération XY/3D ;
  M seul différent → ne crée pas une séparation spatiale ; EMPTY → simple.
- **Stories concernées :** M ; Q01 résolue pour les ordinates ; Q05 pour les membres EMPTY.

### S03 et F02 — Simplicité 03 et Fermeture 02 — MultiLineString

- **Catégorie :** S03 simplicité ; F02 fermeture ; composants LineString.
- **N :** ISO-CD 4.2.16–4.2.17 : simple si chaque membre l’est et si les seules intersections
  entre membres sont sur leurs frontières respectives ; fermé si tous ses membres sont fermés.
- **H / couverture : manquante.** Simplicité individuelle disponible, aucune comparaison entre membres.
- **Scénarios S03 :** lignes disjointes → simple ; extrémité commune de deux lignes ouvertes → simple ;
  extrémité d’une ligne dans l’intérieur d’une autre → non simple ; croisement ou recouvrement → non simple.
- **Scénarios F02 :** deux boucles fermées → fermé ; une ligne ouverte → non fermé ;
  collection EMPTY → non fermée selon ISO-CD 9.3.3–9.3.4 ; membres EMPTY rejetés par le parcours Q05.
- **Stories concernées :** M, dépend F/D ; ne pas imposer S03 ou F02 à toute collection valide.

### V11 — Validité 11 — Relations des membres d’un MultiPolygon

- **Catégorie :** validité, membres Polygon déjà validés.
- **N :** ISO-CD 4.2.18–4.2.19 : intérieurs disjoints, frontières ne se touchant qu’en un nombre fini
  de points ; pas de coupures, pointes ni perforations ; OGC 6.1.14 apporte la même comparaison ciblée.
- **H / couverture : manquante.** Aucun validateur d’interactions entre polygones.
- **Scénarios :** polygones séparés → admissible ; contact en un sommet → admissible ;
  arête commune, surfaces superposées, inclusion d’un polygone dans l’intérieur de l’autre → rejet ;
  polygone dans un trou de l’autre sans contact → admissible si chacun est valide.
- **Stories concernées :** N, dépend K/M ; Q01–Q03.

### V12 — Validité 12 — Faces d’une PolyhedralSurface

- **Catégorie :** validité, chaque face Polygon.
- **N :** ISO-CD 8.5.1, Description 16–17 : faces bien formées et avec Z.
- **H / couverture : partiellement couverte.** `PolyhedralSurfaceValidator` exige Z, faces non vides,
  `PlanarPatch`, Ring et simplicité XYZ ; V09 et C02–C04 ne sont pas intégralement recomposées.
- **Scénarios :** faces XYZ planes valides → passent les contrôles locaux ; face sans Z → rejet ;
  face non plane → rejet ; trou mal placé mais coplanaire → rejet attendu V09, non couvert ;
  face EMPTY dans surface non vide → rejet normatif ISO-CD et historique (Q05).
- **Stories concernées :** O, dépend J/K ; M ignoré dans le plan et la topologie historiques.

### V13 — Validité 13 — Adjacence, incidence et orientation des faces

- **Catégorie :** validité topologique de PolyhedralSurface non vide.
- **N :** ISO-CD 4.2.12 et 8.5.1, Description 2, 4, 18–19 : surface contiguë,
  frontières communes linéaires, au plus deux faces par segment, sens opposés sur une frontière commune.
  OGC 6.1.12.1 décrit ces mêmes exigences d’adjacence et d’orientation.
- **H / couverture : partiellement couverte.** `PolyhedralSurfaceTopology` découpe les arêtes aux sommets
  connus, teste incidence, orientation et connexité ; pas de recherche générale d’intersections entre faces.
- **Scénarios :** deux faces reliées par une arête opposément parcourue → admissibles pour V13 ;
  même sens, trois faces incidentes ou faces disjointes → rejet ;
  partage d’une arête subdivisée → accepté historiquement ; contact par seul sommet → pas de connexion par arête ;
  faces qui se traversent hors arêtes communes → contrôle manquant à spécifier.
- **Stories concernées :** O ; Q02 et Q08.

### V14 — Validité 14 — Surface ouverte et fermeture en solide

- **Catégorie :** validité / fermeture, PolyhedralSurface.
- **Sources en tension :** ISO-CD 8.5.1 Description 6 annonce un polyèdre fermé, alors que Description 7
  et OGC 6.1.12.1 distinguent le cas fermé ; 4.2.12 décrit une surface contiguë sans cette obligation générale.
  **Décision LongitudeOne approuvée sous Q08 :** surface ouverte autorisée par défaut, fermeture exigée séparément.
- **H / couverture : divergente vis-à-vis d’une lecture imposant la fermeture.** Le validateur annonce
  ne pas exiger un solide ; face unique et surface ouverte acceptées. Ce fait ne résout pas la tension ISO.
- **Scénarios :** livre ouvert valide → accepté par défaut, rejeté par le validateur exigeant la fermeture ;
  tétraèdre ou surface de cube valide et fermé → accepté par les deux validateurs ;
  EMPTY Z → valide selon V01, pas une preuve de solide ; son contrôle de fermeture reste à spécifier.
- **Stories concernées :** C, O, P ; deux validateurs selon Q08, sans obligation de compatibilité historique (Q07).

### C01–C04 — Compatibilité 01 à 04 — Compatibilité et validation récursive

- **C01 — type et récursion.** Validité : membre du type autorisé et application des règles des membres.
  N : ISO-CD 7.4.1, 8.3.1, 8.5.1 et 4.2.14–19. Point dans MultiPoint admis ; Polygon rejeté.
- **C02 — famille.** Compatibilité parent/composant Geometry ou Geography, règle historique à formaliser.
  Geometry dans Geography → rejet historique ; même famille → passe ce contrôle ; politique future à préciser ci-dessous.
- **C03 — dimension.** Compatibilité de variante, historique du modèle et non obligation ISO universelle.
  XYZ et XYM rejetés malgré cardinalité 3 ; même variante → passe ce contrôle.
- **C04 — référence spatiale.** Même SRS : N, ISO-CD 4.2.1 et 4.2.14 ; identité PHP : H.
  Même autorité/identifiant → admis ; identifiant ou autorité différents → rejet historique.

**Couverture :** C01 partiellement couverte par le modèle, récursion générale manquante dans le validateur ;
C02–C04 partiellement couvertes par `SameFamilyValidator`, `SameDimensionValidator`,
`SameSpatialReferenceValidator`, à composer explicitement pour chaque relation.
Ces contraintes comparent à une valeur attendue ; elles ne parcourent pas automatiquement les composants.

`SpatialReference::equals` compare autorité et identifiant ; il ne résout pas l’équivalence de deux CRS.
Deux autorités ayant le même entier ne prouvent donc pas l’identité. Une référence spatiale égale
n’atteste ni la validité de ses axes ni l’adéquation entre le nombre d’axes du CRS et le modèle.
ISO-CD 4.2.1 relie dimension et SRS ; le contrôle de cette adéquation dépend d’informations CRS
non disponibles dans la seule identité (ISO 19111, Q04/Q07).
ISO-CD 8.5.1 Description 9 emploie une dimension minimale des faces :
on ne présente pas l’homogénéité stricte du modèle comme une obligation ISO universelle.

**Stories concernées :** D et tous validateurs composites ; Q05/Q07 précisent récursion, diagnostic et compatibilité.
Collection hétérogène en types ou dimensions topologiques : autorisée par 4.2.14 ;
aucune interdiction générale d’intersection de ses membres. Tester Point + Polygon qui le contient →
pas d’échec de validité du seul fait de leur intersection.

### B01 — Restriction métier 01 — Doublons consécutifs : contrôle SQL/MM des points d’une ligne

- **Catégorie :** condition de construction/mutation SQL/MM, à intégrer au contrat de validation des lignes.
  L’identifiant B01 est conservé pour la traçabilité ; ce contrôle n’est pas une simple restriction métier.
- **N :** ISO-CD 7.2.3 appelle `ST_CheckConsecDups` (16.1.7), qui rejette deux membres consécutifs égaux
  et appelle `ST_CheckNulls` (16.1.6), qui interdit les membres null et EMPTY.
  Le critère d’égalité de la procédure doit être distingué de la comparaison PHP historique ;
  la simple lecture du symbole SQL `=` ne justifie pas de le remplacer arbitrairement par `ST_Equals`.
- **H / couverture : partiellement couverte.** `NoConsecutiveDuplicatePointsValidator` compare les points
  adjacents par `equalsTo` et fait partie de Ring historique. Le modèle contrôle aussi les ajouts de points.
  La composition générale LineString manque ; Z est pris en compte et M ignoré selon Q01.
  Les écarts historiques peuvent être corrigés sans contrainte de compatibilité (Q07).
- **Scénarios :** A,A,B → doublon consécutif rejeté ; A,B,A avec A≠B → aucun doublon consécutif ;
  mêmes XY/Z mais M différent → doublon selon Q01, contrairement au contrôle historique ;
  membre EMPTY → rejet par la condition associée 16.1.6, même si les autres points sont distincts.
- **Stories concernées :** F/G ; ce contrôle ne remplace pas la simplicité S01.

## 5. EMPTY, valeurs absentes et égalité

| Situation | Sens | Historique / contrat à spécifier |
| --- | --- | --- |
| `null` ou `''` | Aucune valeur spatiale fournie | Ignorée ; présence gérée par `NotNull`/`NotBlank`. |
| Objet EMPTY | Ensemble géométrique vide typé | V01 ; ne pas lancer aveuglément un minimum de points non vide. |
| Collection sans membres | Collection vide | Valide au sens V01 ; récursion sans membre. |
| Membres EMPTY | Composants présents | Interdictions ISO-CD et portée précisées sous Q05. |
| Point EMPTY dans une LineString | Composant sans position | Interdit : 7.2.3 → 16.1.7 → 16.1.6. |
| Trou/extérieur EMPTY avec trous | Frontière invalide | Rejet dans le parcours ISO-CD, Q05. |
| M absent dans XYZ | Variante sans mesure | Normal ; C03 distingue XYZ de XYM. |
| Ordinate de Point nulle partiellement | Position incomplète | Invalide selon V03 ; pas EMPTY. |

L’égalité historique `Point::equalsTo` dépend de la classe concrète, de la famille, de la dimension,
du SRID entier et de comparaisons strictes des ordinates X/Y/Z/M. Elle ne compare pas l’autorité du SRS.
Elle n’est ni une égalité d’identité complète du SRS ni une définition de `ST_Equals`.
ISO-CD 4.2.2.3 distingue égalité ordinaire (Z/M ignorés), égalité 3D et tolérance définie par l’implémentation.
La comparaison utilise `PHP_FLOAT_EPSILON` pondéré par la grandeur des valeurs selon Q02.
Tester notamment int/float, zéro signé, petite perturbation, grandes coordonnées,
quasi-colinéarité et permutations d’axes avant de stabiliser un algorithme.

## 6. Preuves de couverture dans le dépôt

Chemins sous `lib/Validator/` et `tests/Unit/Validator/` à la révision indiquée en section 1.
Une suite verte atteste l’historique testé, pas la complétude normative.
Selon Q07, aucune ancienne API ou restriction n’est à préserver ; les références ci-dessous sont un inventaire.

Les sources suivantes sont dans `lib/Validator/Constraints/`, sauf mention contraire :

- C02–C04 : `SameFamilyValidator.php`, `SameDimensionValidator.php`, `SameSpatialReferenceValidator.php` ;
  tests `Constraints/CompatibilityTest.php` et `Constraints/RingValidatorTest.php`.
- V04, F01, B01 : `MinimumPointCountValidator.php`, `FirstPointEqualsLastPointValidator.php`,
  `NoConsecutiveDuplicatePointsValidator.php`, `RingValidator.php` ;
  tests `Constraints/RingValidatorTest.php` et `Constraints/ConstraintContractTest.php`.
- S01 XY : `SimpleLineStringValidator.php` ; test `Constraints/SimpleLineStringValidatorTest.php`.
- S01 XYZ : `Simple3dLineStringValidator.php` ; test `Constraints/Simple3dLineStringValidatorTest.php`.
- V10 : `TriangleValidator.php` ; test `Constraints/TriangleValidatorTest.php`.
- V02/V08/V12 : `../Internal/PlanarPatch.php` et `PolyhedralSurfaceValidator.php` ;
  tests `Constraints/BoundaryCandidatesTest.php` et `Constraints/PolyhedralSurfaceValidatorTest.php`.
- V13/V14 : `../Internal/PolyhedralSurfaceTopology.php` ; test `Constraints/PolyhedralSurfaceValidatorTest.php`.
- Assertions : helpers `../LineStringValidation.php`, `../RingValidation.php`, `../TriangleValidation.php` ;
  test `AssertionHelpersTest.php`.

Les helpers `DimensionValidation`, `FamilyValidation`, `SpatialReferenceValidation`,
`LineStringValidation`, `RingValidation`, `TriangleValidation`, `PolyhedralSurfaceValidation`
exposent aussi une API d’assertion avec exceptions ; Q07 autorise leur modification ou suppression.
`tests/Support/SpatialCandidates.php` fournit des candidats permettant de tester des états
que les constructeurs historiques empêchent. Les garanties du modèle ne remplacent pas ces tests.

### Garanties du modèle installé

| Source dans `vendor/longitude-one/spatial-types/lib/Types/` | Garantie observée | Limite |
| --- | --- | --- |
| `AbstractPoint.php` | Toutes les ordinates nulles ou aucune (V03) | Pas de garde générale de finitude. |
| `AbstractLineString.php` | Points non EMPTY, contrôles à l’ajout | Pas de minimum général de deux points. |
| `AbstractPolygon.php` | Compatibilité des anneaux et assertion Ring | Pas de topologie entre trous. |
| `AbstractTriangle.php` | Assertions Triangle | Pas de preuve de non-colinéarité. |
| `AbstractCircularString.php` | Cardinalité et points non vides | Pas de simplicité des arcs. |
| `AbstractCompoundCurve.php` | Types, compatibilité, membres et jonctions | Égalité PHP historique. |
| `AbstractPolyhedralSurface.php` | Assertions sur construction et mutations | Limites V09/V12–V14. |

La famille Geography borne historiquement longitude et latitude ; cela ne prouve pas une topologie géodésique.
Le futur contrat doit recontrôler les objets d’interfaces et les mutations pertinentes selon D,
sans dépendance inverse de `spatial-types` vers `spatial-validator`.

## 7. Questions et réponses

Les réponses consignent les décisions d’Alexandre ; les questions restantes sont ouvertes.
La rédaction du catalogue peut être achevée sans ces choix ; les Stories concernées ne deviennent pas Ready
automatiquement.

### Q01 — Réponse 01 — Z pris en compte, M ignoré

**Décision d’Alexandre du 21 octobre 2026 :** les calculs géométriques prennent en compte Z lorsqu’il est présent
et ignorent toujours M, notamment pour l’égalité des positions, la fermeture, les jonctions et la simplicité.
M est une mesure quelconque, pas nécessairement du temps.
Exemple : `(1,2,3,10)` et `(1,2,3,20)` représentent la même position XYZ.

### Q02 — Réponse 02 — Adapter PHP_FLOAT_EPSILON à la grandeur des valeurs

**Décision d’Alexandre du 21 octobre 2026 :** comparer deux coordonnées finies avec une tolérance
`PHP_FLOAT_EPSILON` multipliée par le facteur `max(1.0, abs($a), abs($b))`.

```php
$facteur = max(1.0, abs($a), abs($b));
$tolerance = PHP_FLOAT_EPSILON * $facteur;
$egales = abs($a - $b) <= $tolerance;
```

Le facteur est symétrique et suit la plus grande valeur absolue ; sous 1, la tolérance reste
`PHP_FLOAT_EPSILON`. Aucun arrondi ni changement des coordonnées n’est effectué.
Les valeurs non finies sont refusées avant cette comparaison (V02).

**Exemples vérifiés :** `0.1 + 0.2` et `0.3`, `1.1 + 2.2` et `3.3`,
`700000.1 + 0.2` et `700000.3` → égales ; `3.0` et `3.000001` → différentes.
Il s’agit d’une égalité numérique approchée, pas d’une distance géographique.
Cette tolérance ne garantit pas de compenser toute accumulation d’erreurs de calcul et n’est pas transitive.
Son application aux tests de planéité, d’orientation et d’intersection reste à préciser selon
les grandeurs intermédiaires ; ne pas appliquer aveuglément un seuil de coordonnées à un déterminant.
Source : [manuel PHP — comparaison des flottants](https://www.php.net/manual/fr/language.types.float.php).
Stories affectées : E–O.

### Q03 — Réponse 03 — Avertir lorsqu’un contrôle géographique est indisponible

**Décision d’Alexandre du 21 octobre 2026 :** un contrôle géographique indisponible produit une violation
avec un message explicite et `payload: ['severity' => 'warning']` sur la contrainte qui la porte.
Cela signifie « contrôle non réalisé », pas « géométrie invalide ». Les contrôles disponibles continuent.
L’application interprète le niveau `warning` ; Symfony conserve cette violation dans sa liste.
Stories affectées : C, H–K, N–P.

### Q04 — Réponse 04 — Coordonnées finies et bornes angulaires Geography

**Décisions d’Alexandre du 21 octobre 2026 :** refuser `INF`, `-INF` et `NAN` dans les ordinates présentes.
Pour la famille Geography, conserver les limites angulaires en degrés, bornes incluses :
latitude de −90 à +90 et longitude de −180 à +180. Un dépassement produit une violation.
Le validateur général ne vérifie pas d’autres limites propres à chaque système de référence.
Des validateurs spécialisés, par exemple Lambert-93, pourront être proposés par contribution.
Les coordonnées partiellement nulles sont déjà invalides selon V03.
Le contrôle des axes et de la dimension du système de référence reste à préciser séparément.
Stories affectées : E, J, O.

### Q05 — Résultat normatif 05 — Composants EMPTY

**Consigne d’Alexandre :** appliquer les interdictions établies par les normes ; ne pas ajouter
une interdiction sans source explicite.

Dans ISO-CD, `ST_CheckNulls` (16.1.6) rejette les membres null **et EMPTY**.
Les méthodes ci-dessous l’appellent directement ou par une autre procédure :

| Objet parent | Composant EMPTY | Parcours normatif ISO-CD vérifié |
| --- | --- | --- |
| LineString | Point interdit | 7.2.3 → 16.1.7 → 16.1.6 |
| CircularString | Point interdit | 7.3.3 → 16.1.7 → 16.1.6 |
| CompoundCurve | Courbe interdite | 7.4.3 → 16.1.6 |
| Polygon / Triangle | Anneau intérieur interdit | 8.3.4 → 8.2.4 → 16.1.6 ; Triangle sans trous (8.4.1) |
| Polygon / Triangle | Anneau extérieur EMPTY interdit pour un objet non vide | 8.3.3 → 8.2.3 ; anneau exigé, 7.1.8 |
| PolyhedralSurface | Face interdite | 8.5.3 → 16.1.6 |
| GeometryCollection | Membre interdit dans ce parcours SQL/MM | 9.1.3 → 16.1.6 |
| MultiPoint | Point interdit | 9.2.3 → 16.1.8 → 16.1.6 |
| MultiLineString | Ligne interdite | 9.4.3 → 16.1.10 → 16.1.6 |
| MultiPolygon | Polygon interdit | 9.6.3 → 16.1.15 → 16.1.6 |

Une liste sans membre n’est pas une liste contenant un membre EMPTY : la première peut représenter
un objet EMPTY valide. Un Polygon sans trou n’a pas besoin d’un anneau intérieur EMPTY.
Exemples : LineString sans point → EMPTY valide ; LineString contenant un Point EMPTY → invalide ;
Polygon non vide avec un trou EMPTY → composant rejeté dans le parcours ISO-CD.
Une collection contenant un Point non vide et un Point EMPTY est rejetée par ISO-CD 9.1.3 → 16.1.6.

**Limite de portée :** ces interdictions sont explicites dans les méthodes SQL/MM du Committee Draft.
OGC SFA 1.2.1, 6.1.3.1 ne formule pas cette interdiction générale pour GeometryCollection.
Ne pas présenter le résultat SQL/MM comme une obligation commune à toutes les normes ;
son application au contrat générique de collection doit conserver cette distinction.
Aucune interdiction supplémentaire n’est déduite du seul silence d’un texte.

**Fermeture du vide :** un MultiLineString EMPTY est non fermé selon ISO-CD 9.3.3–9.3.4,
qui renvoient zéro pour un MultiCurve EMPTY ; cela ne le rend pas géométriquement invalide.
Stories affectées : C–O. Aucun changement d’exécution dans ce catalogue.

### Q06 — Résultat normatif 06 — Quatre positions minimum pour un anneau linéaire

Un anneau linéaire non vide comporte au moins quatre positions, dont la dernière répète la première : A, B, C, A.
Ce minimum ne remplace pas les contrôles de fermeture, de simplicité et de non-dégénérescence.

- **Triangle :** exactement quatre points, explicitement dans ISO-CD 8.4.1, Description 2 ; aucun trou.
- **Polygon :** extérieur et trous sont des anneaux linéaires (ISO-CD 8.3.1, Description 2–3).
- **Anneau linéaire :** minimum quatre positions et extrémités coïncidentes, explicitement dans
  [OGC 07-036, GML 3.2.1, §10.5.8](https://docs.ogc.org/is/07-036/07-036.pdf), page imprimée 78.
  ISO-CD 4.2.5 définit l’anneau linéaire par fermeture et simplicité, sans y écrire ce minimum numérique.

Ce résultat concerne les anneaux **linéaires** : un cercle porté par une CircularString peut être défini
par trois points et ne reçoit pas ce seuil de quatre.
Stories affectées : F, G, L. Le minimum historique de Ring est donc conservé pour les anneaux linéaires.

### Q07 — Réponse 07 — Aucune compatibilité historique à préserver

**Décision d’Alexandre du 21 octobre 2026 :** rien n’a été publié ; toutes les anciennes API et restrictions
peuvent être modifiées ou supprimées, y compris les helpers, messages et exceptions.
Les comportements existants servent uniquement d’inventaire, pas de contrat à conserver.
Les exigences normatives et les décisions fonctionnelles déjà prises restent applicables.
Stories affectées : C, D, F, G, L, O, P.

### Q08 — Réponse 08 — Deux validateurs, surface ouverte autorisée par défaut

**Décision d’Alexandre du 21 octobre 2026 :** créer deux validateurs de PolyhedralSurface.
Le premier autorise une surface ouverte ; le second vérifie en plus que le polyèdre est fermé.
La validation générale appelle par défaut le premier : le livre ouvert est accepté s’il satisfait les autres règles.
Le livre ouvert échoue au contrôle supplémentaire de fermeture ; une surface de cube valide le satisfait.
Cette décision tranche le choix LongitudeOne face à l’incohérence ISO-CD relevée dans V14.
Stories affectées : C, O, P.

### Q09 — Réponse 09 — Avertir si un trou a le même sens que l’extérieur

**Décision d’Alexandre du 21 octobre 2026 :** un anneau intérieur parcouru dans le même sens que l’extérieur
produit une violation avec `payload: ['severity' => 'warning']` sur la contrainte qui la porte.
Comparer les sens depuis le même côté du plan ; ne pas réorienter les anneaux automatiquement.
Exemple : extérieur antihoraire et trou antihoraire → avertissement ; trou horaire → aucun avertissement d’orientation.
Stories affectées : J, K, O, P.

### Précisions restantes avant les Stories concernées

Les réponses précédentes sont acquises. Les points ci-dessous restent à instruire ou à approuver par Alexandre ;
ils ne bloquent pas la livraison du catalogue, mais empêchent de déclarer les Stories concernées Ready sans refinement.

| Point | Source | Stories |
| --- | --- | --- |
| Tolérance des calculs intermédiaires | Q02, V08/V09/V13 | H–O |
| Axes et dimension du SRS | ISO-CD 4.2.1, ISO 19111 non inspecté | D/E/J/O |
| Fermeture d’une surface EMPTY | ISO-CD 8.5.1, Q08 | O/P |
| Famille et variante des composants | C02/C03, garanties du modèle, Q07 | D et composites |
| Profil des collections avec membres EMPTY | ISO-CD 9.1.3/16.1.6, OGC 6.1.3.1 | C/D/M/P |
| Simplicité des arcs et intersections de faces | V05/S01/V13 ; ISO-CD 4.2.4/8.5.1 | H/I/O |

- **Calculs intermédiaires :** choisir une normalisation adaptée ou un seuil tenant compte de leur échelle,
  sans remettre en cause la formule Q02 pour deux coordonnées. Conséquence : cohérence des tests de plan,
  orientation et intersection. Résultats proches de la limite encore dépendants de cette spécification.
- **Axes et dimension du SRS :** vérifier avec des métadonnées CRS ou annoncer ce contrôle indisponible.
  Conséquence : besoin de données supplémentaires ou couverture limitée ; aucune déduction depuis le seul entier SRID.
  Analyser ISO 19111 avant une conclusion normative.
- **Surface EMPTY :** préciser si le validateur exigeant la fermeture la refuse ou l’accepte comme cas vide.
  Conséquence : résultat du scénario EMPTY avec ce validateur encore indéterminé ; validité EMPTY par défaut acquise.
- **Famille et variante :** conserver l’homogénéité stricte ou définir explicitement des mélanges admissibles.
  Conséquence : composants acceptés et espace de calcul ; Q07 autorise les modifications, sans choisir cette politique.
- **Collections EMPTY :** retenir le parcours SQL/MM qui rejette ces membres ou proposer un profil distinct.
  Conséquence : acceptation différente selon le profil ; aucune prétention d’exigence identique entre SQL/MM et OGC.
- **Arcs et faces :** spécifier les contrôles de toutes les interactions, ou annoncer celles non prises en charge.
  Conséquence : scénarios d’auto-intersection d’arcs et de traversée de faces à compléter avant H/I/O.
  Les seuls tests de cordes ou d’arêtes ne prouvent pas la simplicité ou la validité globale.

## 8. Affectation aux Stories proposées

Les lettres A à P identifient les Stories de l’Epic, distinctes des codes de règles tels que S01.
A correspond à l’issue #8 et B à l’issue #10. Les autres lettres ne sont pas des numéros d’issues GitHub.

| Story | Règles et livrable à préciser | Dépendances et décisions |
| --- | --- | --- |
| A / #8 | Présent catalogue, inventaire et registre | Relecture indépendante de traçabilité |
| B / #10 | Contrat d’entrée selon les conventions Symfony | A ; L-INPUT-01 |
| C | Entrée Symfony générique et couverture annoncée | A/B ; Q01, Q03, Q05, Q07 |
| D | C01–C04, parcours, chemins, groupes, codes | C ; Q05, Q07 ; préparer avant composites |
| E | V02/V03/V16, coordonnées communes et bornes Geography | A ; Q02, Q04 |
| F | V04, S01, B01 selon contrat | D/E ; Q01, Q05–Q07 |
| G | F01 et simplicité des anneaux | F ; Q01, Q02, Q06, Q07 |
| H | V05 et simplicité des arcs si demandée | D/E ; Q01–Q05 |
| I | V06, jonctions et simplicité globale si demandée | F/H ; Q01–Q03, Q05 |
| J | V07/V08/V15, structure et plan | D/G ; Q01–Q05, Q09 |
| K | V09/V15, relations et orientation | J ; Q01–Q03, Q09 |
| L | V10, composition des règles Polygon | G/J ; Q01, Q05–Q07 |
| M | Collections, C01–C04, S02/S03/F02 optionnelles | D/E/F ; Q01–Q05, Q07 |
| N | V11, interactions entre Polygon | K/M ; Q01–Q03 |
| O | V12–V14, faces, interactions et deux validateurs selon Q08 | J/K ; Q01–Q05, Q07–Q09 |
| P | API, composition, diagnostics, limites publiables | Contrats approuvés C/D et couverture réellement livrée |

Aucun parcours récursif, code de violation ou API publique n’est arrêté par ce fichier.
La documentation utilisateur détaillée de `spatial-docs` reste prévue à partir de `1.0.0-RC.0`.

## 9. Vérification et état de livraison

- [x] Inventaire des sources et révisions consigné.
- [x] Règles, variantes, scénarios et écarts historiques identifiés.
- [x] Orientation structurelle Geography reçue d’Alexandre, topologie différée.
- [x] Espace du générique Geometry approuvé : XYZ si Z est présent, sinon XY (L-DIM-01).
- [x] Conventions Symfony retenues pour les entrées, conformément à la story #10 (L-INPUT-01).
- [x] Décisions reçues tracées ; précisions restantes, options et conséquences affectées aux Stories concernées.
- [x] Relecture indépendante finale : aucun blocage documentaire identifié au regard des critères de la story #8.
- [x] `composer test` : 112 tests, 177 assertions ; `composer quality` : succès.
- [ ] Intégration et Definition of Done complète : hors de cette préparation locale.

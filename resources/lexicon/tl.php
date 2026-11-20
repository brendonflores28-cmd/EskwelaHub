<?php

/*
| Tagalog / Taglish sentiment words (ang pagsusuri ay para sa mga komento ng survey).
| Weight 1 is normal, 2 is strong.
*/
return [
    'positive' => [
        'maganda' => 1, 'mabuti' => 1, 'mabilis' => 1, 'bilis' => 1, 'magaling' => 1, 'galing' => 1, 'salamat' => 1,
        'maayos' => 1, 'ayos' => 1, 'madali' => 1, 'matulungin' => 1, 'magalang' => 1, 'mabait' => 1, 'masaya' => 1,
        'sulit' => 1, 'malinaw' => 1, 'maasikaso' => 1, 'palakaibigan' => 1, 'malinis' => 1, 'swabe' => 1,
        'astig' => 1, 'napakabuti' => 2, 'napakaganda' => 2, 'napakabilis' => 2, 'mahusay' => 1, 'husay' => 1,
        'kontento' => 1, 'natuwa' => 1, 'nagustuhan' => 1, 'organisado' => 1, 'magiliw' => 1, 'mapagpasensya' => 1,
    ],
    'negative' => [
        'mabagal' => 1, 'bagal' => 1, 'pangit' => 2, 'masama' => 1, 'mahirap' => 1, 'magulo' => 1, 'nakakainis' => 2,
        'galit' => 2, 'tagal' => 1, 'matagal' => 1, 'pagod' => 1, 'sayang' => 1, 'palpak' => 2, 'mali' => 1,
        'problema' => 1, 'pabaya' => 2, 'bastos' => 2, 'masungit' => 2, 'sungit' => 2, 'nakakadismaya' => 2,
        'kulang' => 1, 'abala' => 1, 'nawala' => 1, 'napakabagal' => 2, 'napakatagal' => 2, 'nainis' => 1,
        'dismayado' => 2, 'reklamo' => 1, 'pahirap' => 2,
    ],
    'negators' => ['hindi', 'di', 'wala', 'walang', 'ayaw', 'huwag', 'hwag', 'di naman'],
    'intensifiers' => ['sobrang', 'napaka', 'talaga', 'masyado', 'grabe', 'super', 'sobra'],
];

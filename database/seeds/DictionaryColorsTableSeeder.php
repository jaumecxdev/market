<?php

use Illuminate\Database\Seeder;

class DictionaryColorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \DB::table('dictionaries')->insert(['es' => 'Blanco', 'en' => 'White']);
        \DB::table('dictionaries')->insert(['es' => 'Negro', 'en' => 'Black']);
        \DB::table('dictionaries')->insert(['es' => 'Verde', 'en' => 'Green']);
        \DB::table('dictionaries')->insert(['es' => 'Rojo', 'en' => 'Red']);
        \DB::table('dictionaries')->insert(['es' => 'Gris', 'en' => 'Gray']);
        \DB::table('dictionaries')->insert(['es' => 'Gris', 'en' => 'Grey']);
        \DB::table('dictionaries')->insert(['es' => 'Azul', 'en' => 'Blue']);
        \DB::table('dictionaries')->insert(['es' => 'Amarillo', 'en' => 'Yellow']);
        \DB::table('dictionaries')->insert(['es' => 'Rosa', 'en' => 'Pink']);
        \DB::table('dictionaries')->insert(['es' => 'Púrpura', 'en' => 'Purple']);
        \DB::table('dictionaries')->insert(['es' => 'Plata', 'en' => 'Silver']);
        \DB::table('dictionaries')->insert(['es' => 'Oro', 'en' => 'Gold']);
        \DB::table('dictionaries')->insert(['es' => 'Marrón', 'en' => 'Brown']);
        \DB::table('dictionaries')->insert(['es' => 'Azul oscuro', 'en' => 'Dark Blue']);
        \DB::table('dictionaries')->insert(['es' => 'Marrón oscuro', 'en' => 'Dark Brown']);
        \DB::table('dictionaries')->insert(['es' => 'Verde oscuro', 'en' => 'Dark Green']);
        \DB::table('dictionaries')->insert(['es' => 'Gris oscuro', 'en' => 'Dark Grey']);
        \DB::table('dictionaries')->insert(['es' => 'Fucsia', 'en' => 'Fuchsia']);
        \DB::table('dictionaries')->insert(['es' => 'Dorado', 'en' => 'Golden Yellow']);
        \DB::table('dictionaries')->insert(['es' => 'Marfil', 'en' => 'Ivory']);
        \DB::table('dictionaries')->insert(['es' => 'Caqui', 'en' => 'Khaki']);
        \DB::table('dictionaries')->insert(['es' => 'Lavanda', 'en' => 'Lavender']);
        \DB::table('dictionaries')->insert(['es' => 'Marrón claro', 'en' => 'Light Brown']);
        \DB::table('dictionaries')->insert(['es' => 'Verde claro', 'en' => 'Light Green']);
        \DB::table('dictionaries')->insert(['es' => 'Granate', 'en' => 'Maroon']);
        \DB::table('dictionaries')->insert(['es' => 'Azul marino', 'en' => 'Navy Blue']);
        \DB::table('dictionaries')->insert(['es' => 'Oliva', 'en' => 'Olive']);
        \DB::table('dictionaries')->insert(['es' => 'Naranja', 'en' => 'Orange']);
        \DB::table('dictionaries')->insert(['es' => 'Azul eléctrico', 'en' => 'Peacock Blue']);
        \DB::table('dictionaries')->insert(['es' => 'Gris rosado', 'en' => 'Pinkish Grey']);
        \DB::table('dictionaries')->insert(['es' => 'Rosa oro', 'en' => 'Rose Gold']);
        \DB::table('dictionaries')->insert(['es' => 'Rosa rojo', 'en' => 'Rose Red']);
        \DB::table('dictionaries')->insert(['es' => 'Madera', 'en' => 'Sliver']);
        \DB::table('dictionaries')->insert(['es' => 'Violeta', 'en' => 'Violet']);
        \DB::table('dictionaries')->insert(['es' => 'Rojo melón', 'en' => 'Watermelon Red']);
        \DB::table('dictionaries')->insert(['es' => 'Rojo vino', 'en' => 'Wine Red']);
        \DB::table('dictionaries')->insert(['es' => 'Café', 'en' => 'Caffee']);
        \DB::table('dictionaries')->insert(['es' => 'Cobre', 'en' => 'Copper']);
        \DB::table('dictionaries')->insert(['es' => 'Champán', 'en' => 'Champagne']);
        \DB::table('dictionaries')->insert(['es' => 'Otro', 'en' => 'Other']);

    }
}

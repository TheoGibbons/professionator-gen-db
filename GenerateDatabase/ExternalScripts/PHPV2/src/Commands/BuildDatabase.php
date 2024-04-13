<?php

namespace Professionator\Commands;


use Professionator\Env;
use Professionator\Files;
use Professionator\Models\Recipe;
use Professionator\Utils;

class BuildDatabase
{

    public function index(): void
    {
        foreach (Env::get('VERSIONS') as $verKey => $versions) {

            $professionRecipeArray = [];

            echo "Starting $verKey\n";

            foreach ($versions as $professionName => $professionData) {

                echo "Starting $professionName\n";

                $recipes = $this->getRecipes($professionData['url']);

                $professionRecipeArray = array_merge($professionRecipeArray, [
                    $professionName => $recipes
                ]);

                echo " done " . count($recipes) . " recipes found\n";

            }

            echo "outputting database...";
            $this->outputDatabase($verKey, $professionRecipeArray);
            echo " done\n";

        }
    }

    private function getRecipes(string $url): array
    {
        $contents = Utils::getFileContents($url);

        // only keep the one line starting with "var listviewspells = " and ending with ";" (only keep everything between the brackets)
//        if (!preg_match('/^var listviewspells = (.*?);$/', $contents, $matches)) {
        if (!preg_match('/\nvar listviewspells = (.*?);\n/', $contents, $matches)) {
            throw new \Exception("html not in expected format for url: $url");
        }

        // Convert the JavaScript array to a valid json string array
        // It looks like we only need to add quotes around the "quality" and "popularity" keys for this
        $arrayString = $matches[1];
        $arrayString = str_replace(",quality:", ",\"quality\":", $arrayString);
        $arrayString = str_replace(",popularity:", ",\"popularity\":", $arrayString);

        // decode the json
        $arrayString = json_decode($arrayString, true);

        // Convert the array to Recipe objects
        return array_map(fn($recipe) => new Recipe($recipe), $arrayString);
    }

    private function outputDatabase(string $verKey, array $recipes): void
    {
        $html = $this->transformRecipesToLua($verKey, $recipes);

        file_put_contents(Files::database("/{$verKey}Recipes.lua"), $html);
    }

    private function transformRecipesToLua(string $verKey, array $recipes)
    {
        $html = "-- AUTO GENERATED FILE! DO NOT EDIT!

---@type ProfessionatorDB
local ProfessionatorDB = ProfessionatorLoader:ImportModule(\"ProfessionatorDB\")

";


        foreach ($recipes as $professionName => $professionRecipes) {
            $html .= "ProfessionatorDB.{$professionName} = {\n";

            $totalCount = count($professionRecipes);
            $i = 0;
            $modPrint = 1;
            echo "Transforming $professionName ($totalCount recipes)...\n";

            /** @var Recipe $recipe */
            foreach ($professionRecipes as $recipe) {

                if ($i % $modPrint === 0) {
                    echo "\r$i/$totalCount";
                }

                $html .= $recipe->toLua();

                $i++;
            }
            $html .= "}\n\n\n";

            echo "\r$totalCount/$totalCount\n";
        }

        return $html;
    }

}
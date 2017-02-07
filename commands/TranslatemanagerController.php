<?php

namespace lajax\translatemanager\commands;

use lajax\translatemanager\models\Language;
use lajax\translatemanager\Module;
use lajax\translatemanager\services\Generator;
use lajax\translatemanager\services\Optimizer;
use lajax\translatemanager\services\Scanner;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Command for scanning and optimizing project translations
 *
 * @author Tobias Munk <schmunk@usrbin.de>
 * @since 1.2.8
 */
class TranslatemanagerController extends Controller {

    /**
     * @inheritdoc
     */
    public $defaultAction = 'help';

    /**
     * Display this help.
     */
    public function actionHelp() {
        $this->run('/help', [$this->id]);
    }

    /**
     * Detecting new language elements.
     */
    public function actionScan() {
        $this->stdout("Scanning translations...\n", Console::BOLD);
        $scanner = new Scanner();

        $items = $scanner->run();
        $this->stdout("{$items} new item(s) inserted into database.\n");
    }

    /**
     * Removing unused language elements.
     */
    public function actionOptimize() {
        $this->stdout("Optimizing translations...\n", Console::BOLD);
        $optimizer = new Optimizer();
        $items = $optimizer->run();
        $this->stdout("{$items} removed from database.\n");
    }

    public function actionGenerate($dir, $languageIds = null){
        $this->stdout("Generating javascript translations...\n", Console::BOLD);

        /** @var Module $module */
        $module = Yii::$app->getModule('translatemanager');
        $module->tmpDir = $dir;

        if (empty($languageIds)) {
            $languageIds = Language::find()
                ->andWhere(['status' => Language::STATUS_ACTIVE])
                ->select('language_id')
                ->column();
        }

        if (count($languageIds)) {
            foreach ($languageIds as $languageId) {
                $generator = new Generator($module, $languageId);
                $generator->run();
            }
            $this->stdout("Generated javascript translations for: " . implode(', ', $languageIds) . "\n");
        } else {
            $this->stdout("No languages to generate\n");
        }
    }
}

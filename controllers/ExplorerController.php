<?php

class ExplorerController extends BaseController {

    const MODULE_KEY = 'explorer';
    const DISPLAY_SOURCE_CODE_TPL_KEY = "DISPLAY_SOURCE_CODE";

    public function run(Resource $resource) {
        Logger::getLogger()->LogInfo("Serving Index Controller");
        $inputParams = $resource->getParams();
        $pid = $inputParams[RequestManager::INPUT_PARAM_PID];
        $language = $inputParams[RequestManager::INPUT_PARAM_LANG];
        $category = $inputParams[RequestManager::INPUT_PARAM_CATE];
        $this->displaySourceCode($language, $category, $pid);
        Display::render(strtoupper($resource->getKey()));
    }

    public function displaySourceCode($lang, $category, $pid) {
        $programDetails = $this->getSourceDetails($lang, $category, $pid);
        if (!empty($programDetails)) {
            $sourceCode = $this->getSourceCode($programDetails);
            echo $this->getProcessedTemplate($programDetails, $sourceCode);
        } else {
            $this->smarty->display("string:".Display::render("ERROR_NO_ITEM"));
        }
    }

    private function getSourceDetails($lang, $category, $pid) {
        $query = 'SELECT '.ProgramDetails_DBTable::DB_TABLE_NAME.'.*,'.
            Category_DBTable::DB_TABLE_NAME.'.'.Category_DBTable::CATEGORY_NAME.' AS category_name,'.
            Language_DBTable::DB_TABLE_NAME.'.'.Language_DBTable::LANGUAGE_NAME.' AS language_name FROM '.
            ProgramDetails_DBTable::DB_TABLE_NAME.' INNER JOIN '.Category_DBTable::DB_TABLE_NAME.' ON '.
            Category_DBTable::DB_TABLE_NAME.'.'.Category_DBTable::CATEGORY_ID.' = '.
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::FK_CATEGORY_ID.' INNER JOIN '.
            Language_DBTable::DB_TABLE_NAME.' ON '.Language_DBTable::DB_TABLE_NAME.'.'.Language_DBTable::LANGUAGE_ID.' = '.
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::FK_LANGUAGE_ID.' WHERE '.
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::PROGRAM_ID."=? AND ".
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::FK_LANGUAGE_ID."=? AND ".
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::FK_CATEGORY_ID."=? AND ".
            ProgramDetails_DBTable::DB_TABLE_NAME.'.'.ProgramDetails_DBTable::IS_DELETED."= '0'";
        $bindParams = array($pid, $lang, $category);
        $resultSet = DBManager::executeQuery($query, $bindParams, true);
        return current($resultSet);
    }

    private function getSourceCode($programDetails) {
        $filePath = Configuration::get('CODE_BASE_DIR');
        $filePath .= $programDetails[ProgramDetails_DBTable::FK_LANGUAGE_ID].'/';
        $filePath .= $programDetails[ProgramDetails_DBTable::FK_CATEGORY_ID].'/';
        $filePath .= $programDetails[ProgramDetails_DBTable::STORED_FILE_NAME];
        $fileContents = file_get_contents($filePath);
        return $fileContents;
    }

    private function getProcessedTemplate($programDetails, $sourceCode) {
        $rawContents = Display::render(self::DISPLAY_SOURCE_CODE_TPL_KEY);
        $this->smarty->assign("PROGRAM_DETAILS", $programDetails);
        $this->smarty->assign("LANGUAGE", ucfirst(strtolower($programDetails[ProgramDetails_DBTable::FK_LANGUAGE_ID])));
        $this->smarty->assign("SOURCE_CODE", htmlentities($sourceCode));
        $this->smarty->assign("SOURCE_STATS", $this->getSourceStats($sourceCode));
        return $this->smarty->fetch('string:'.$rawContents);
    }

    private function getSourceStats($sourceCode) {
        return array(
            'lineCount' => substr_count($sourceCode, PHP_EOL),
            'wordCount' => str_word_count($sourceCode),
            'charCount' => strlen($sourceCode),
            'fileSize' => round((strlen($sourceCode) / 1024), 3)
        );
    }
}
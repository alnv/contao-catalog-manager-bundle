<?php

namespace Alnv\ContaoCatalogManagerBundle\AI;

use Alnv\ContaoCatalogManagerBundle\Views\Master;
use Alnv\ContaoOpenAiAssistantBundle\Helpers\Toolkit;
use Alnv\ContaoOpenAiAssistantBundle\Library\Parser;

class AiChatComponentParser extends Parser
{

    public function getAdditionalInstructions(): string
    {

        return 'Du bist ein intelligenter Assistent, der darauf spezialisiert ist, präzise und informative Antworten auf gestellte Fragen zu geben. Du kannst gerne die URL zur Seite in deine Antwort einbauen.
            Deine Aufgabe ist es, den Vektor-Store nach relevanten Informationen zu durchsuchen, die Frage umfassend zu beantworten und die Quellen der verwendeten Informationen im JSON-Format anzugeben.
            Formuliere eine prägnante Antwort und stelle sicher, dass du keine direkten Quellenangaben in deinem Text machst. 
            Stattdessen, hänge die Quellen im folgenden JSON-Format an: ' .
            '
            ```json
            {
                "entities": [
                    {
                        "CATALOG-ID": "2",
                        "CATALOG-TABLE": "…",
                        "CATALOG-TEMPLATE": "…",
                        "CATALOG-URL": "https://…"
                    },
                    {
                        "CATALOG-ID": "2",
                        "CATALOG-TABLE": "…",
                        "CATALOG-TEMPLATE": "…",
                        "CATALOG-URL": "https://…"
                    }
                ]
            }
            ```
            ' .
            'Falls möglich, präsentiere deine Antwort in Form einer HTML-Tabelle oder Liste, um die Informationen klar und übersichtlich darzustellen. Beachte folgende Reihenfolge: Antworte erst mit einem kurzen einleitenden Satz, dann gebe das JSON aus und dann kommt die präzise und informative Antwort auf gestellte Fragen.';
    }

    public function parseMessages($strMessage, $arrMessages, $arrOptions = []): string
    {

        $arrEntities = Toolkit::getJsonFromMessage($strMessage, '<code class="json">', '</code>');
        if (empty($arrEntities)) {
            $arrEntities = Toolkit::getJsonFromMessage($strMessage, '<code>', '</code>');
        }

        $strView = '';
        foreach (($arrEntities['entities'] ?? []) as $arrEntity) {

            $strID = $arrEntity['CATALOG-ID'] ?? '';
            $strTable = $arrEntity['CATALOG-TABLE'] ?? '';
            $strTemplate = $arrEntity['CATALOG-TEMPLATE'] ?? '';
            $strUrl = $arrEntity['CATALOG-URL'] ?? '';

            if ($strID && $strTable && $strTemplate) {
                $arrView = (new Master($strTable, [
                    'alias' => $strID,
                    'masterUrl' => $strUrl,
                    'template' => $strTemplate
                ]))->parse()[0] ?? [];

                if (!empty($arrView) && isset($arrView['template'])) {
                    $strView .= $arrView['template'];
                }
            }
        }

        if ($strView) {
            $strMessage = Toolkit::replace($strMessage, '<code class="json">', '</code>', $strView);
            $strMessage = str_replace('<code class="json">', '<div class="catalog-results">', $strMessage);
            $strMessage = str_replace('</code>', '</div>', $strMessage);
            $strMessage = str_replace('<pre>', '', $strMessage);
            $strMessage = str_replace('</pre>', '', $strMessage);
        }

        return $strMessage;
    }
}
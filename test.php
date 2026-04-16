<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
if (!$USER->IsAdmin()) {
    LocalRedirect('/');
}
\Bitrix\Main\Loader::includeModule('iblock');
$row = 1;
$IBLOCK_ID = 3;

$el = new CIBlockElement;
$arProps = [];

$rsElement = CIBlockElement::getList([], ['IBLOCK_ID' => 3],
    false, false, ['ID', 'NAME']);

    // CIBlockElement::GetList(
    //     array arOrder = Array("SORT"=>"ASC"),
    //     array arFilter = Array(),
    //     mixed arGroupBy = false,
    //     mixed arNavStartParams = false,
    //     array arSelectFields = Array()
    // );

while ($ob = $rsElement->GetNextElement()) {
    $arFields = $ob->GetFields();
    $key = str_replace(['»', '«', '(', ')'], '', $arFields['NAME']);
    $key = strtolower($key);
    $arKey = explode(' ', $key);
    $key = '';
    foreach ($arKey as $part) {
        if (strlen($part) > 2) {
            $key .= trim($part) . ' ';
        }
    }
    $key = trim($key);
    $arProps['COMPANY'][$key] = $arFields['ID'];
}

$rsProp = CIBlockProperty::GetList(
    ["SORT" => "ASC", "VALUE" => "ASC"],
    ['IBLOCK_ID' => $IBLOCK_ID]
);

/*
CDBResult CIBlockProperty::GetList(
	array arOrder = Array(),
	array arFilter = Array()
);
*/

while ($arProp = $rsProp->Fetch()) {
    $key = trim($arProp['NAME']);
    $arProps['PROPERTY'][$key] = $arProp['ID'];
}

$rsElements = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID']);
while ($element = $rsElements->GetNext()) {
    CIBlockElement::Delete($element['ID']);
}

if (($handle = fopen("customers-100.csv", "r")) !== false) {
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if ($row == 1) {
            $row++;
            continue;
        }
        $row++;

        $PROP['PERSONAL_ID'] = isset($data[1]) ? $data[1] : '';
        $PROP['FIRST_NAME'] = isset($data[2]) ? $data[2] : '';
        $PROP['SECOND_NAME'] = isset($data[3]) ? $data[3] : '';
        $PROP['CITY'] = isset($data[5]) ? $data[5] : '';
        $PROP['COUNTRY'] = isset($data[6]) ? $data[6] : '';
        $PROP['EMAIL'] = isset($data[9]) ? $data[9] : '';
        $PROP['SUBSCRIPTION_DATE'] = isset($data[10]) ? $data[10] : date("m.d.y");
        $PROP['PHONE1'] = isset($data[7]) ? $data[7] : '';
        $PROP['PHONE2'] = isset($data[8]) ? $data[8] : '';
        $PROP['WEBSITE'] = isset($data[11]) ? $data[11] : '';

        foreach ($PROP as $key => &$value) {
            $value = trim($value);
            $value = str_replace('\n', '', $value);
        }
        
        $arLoadProductArray = [
            "MODIFIED_BY" => $USER->GetID(),
            "IBLOCK_SECTION_ID" => false,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => isset($data[4]) ? $data[4] : 'Без названия',
            "ACTIVE" => 'Y',
        ];

        if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
            echo "Добавлен элемент с ID : " . $PRODUCT_ID . "<br>";
        } else {
            echo "Error: " . $el->LAST_ERROR . '<br>';
        }
    }
    fclose($handle);
}
<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 10/5/17
 * Time: 11:29 AM.
 */

namespace App\Utils\HumanReadable;

class CustomTinyButStrong extends \clsTinyButStrong
{
    public function meth_Merge_SectionNormal(&$BDef, &$Src)
    {
        $Txt = $BDef->Src;
        $LocLst = &$BDef->LocLst;
        $iMax = $BDef->LocNbr;
        $PosMax = strlen($Txt);

        if ($Src === false) { // Erase all fields
            $x = '';
            // Chached locators
            for ($i = $iMax; $i > 0; --$i) {
                if ($LocLst[$i]->PosBeg < $PosMax) {
                    $this->meth_Locator_Replace($Txt, $LocLst[$i], $x, false);
                    if ($LocLst[$i]->Enlarged) {
                        $PosMax = $LocLst[$i]->PosBeg;
                        $LocLst[$i]->PosBeg = $LocLst[$i]->PosBeg0;
                        $LocLst[$i]->PosEnd = $LocLst[$i]->PosEnd0;
                        $LocLst[$i]->Enlarged = false;
                    }
                }
            }

            // Uncached locators
            if ($BDef->Chk) {
                $BlockName = &$BDef->Name;
                $Pos = 0;
                while ($Loc = $this->meth_Locator_FindTbs($Txt, $BlockName, $Pos, '.')) {
                    $Pos = $this->meth_Locator_Replace($Txt, $Loc, $x, false);
                }
            }
        } else {
            // Cached locators
            for ($i = $iMax; $i > 0; --$i) {
                if ($LocLst[$i]->PosBeg < $PosMax) {
                    if ($LocLst[$i]->IsRecInfo) {
                        if ($LocLst[$i]->RecInfo === '#') {
                            $this->meth_Locator_Replace($Txt, $LocLst[$i], $Src->RecNum, false);
                        } else {
                            $this->meth_Locator_Replace($Txt, $LocLst[$i], $Src->RecKey, false);
                        }
                    } else {
                        $this->meth_Locator_Replace($Txt, $LocLst[$i], $Src->CurrRec, 0);
                    }
                    if ($LocLst[$i]->Enlarged) {
                        $PosMax = $LocLst[$i]->PosBeg;
                        $LocLst[$i]->PosBeg = $LocLst[$i]->PosBeg0;
                        $LocLst[$i]->PosEnd = $LocLst[$i]->PosEnd0;
                        $LocLst[$i]->Enlarged = false;
                    }
                }
            }

            // Unchached locators
            if ($BDef->Chk) {
                $BlockName = &$BDef->Name;
                foreach ($Src->CurrRec as $key => $val) {
                    $Pos = 0;
                    $Name = $BlockName.'.'.$key;
                    while ($Loc = $this->meth_Locator_FindTbs($Txt, $Name, $Pos, '.')) {
                        $Pos = $this->meth_Locator_Replace($Txt, $Loc, $val, 0);
                    }
                }
                $Pos = 0;
                $Name = $BlockName.'.#';
                while ($Loc = $this->meth_Locator_FindTbs($Txt, $Name, $Pos, '.')) {
                    $Pos = $this->meth_Locator_Replace($Txt, $Loc, $Src->RecNum, 0);
                }
                $Pos = 0;
                $Name = $BlockName.'.$';
                while ($Loc = $this->meth_Locator_FindTbs($Txt, $Name, $Pos, '.')) {
                    $Pos = $this->meth_Locator_Replace($Txt, $Loc, $Src->RecKey, 0);
                }
            }
        }

        // Automatic sub-blocks
        if (isset($BDef->AutoSub)) {
            for ($i = 1; $i <= $BDef->AutoSub; ++$i) {
                $name = $BDef->Name.'_sub'.$i;
                $query = '';
                $col = $BDef->Prm['sub'.$i];
                if ($col === true) {
                    $col = '';
                }
                $col_opt = (substr($col, 0, 1) === '(') && (substr($col, -1, 1) === ')');
                if ($col_opt) {
                    $col = substr($col, 1, strlen($col) - 2);
                }
                if ($col === '') {
                    // $col_opt cannot be used here because values which are not array nore object are reformated by $Src into an array with keys 'key' and 'val'
                    $data = &$Src->CurrRec;
                } elseif (is_object($Src->CurrRec)) {
                    $data = $Src->CurrRec->$col;
                } else {
                    if (array_key_exists($col, $Src->CurrRec)) {
                        $data = &$Src->CurrRec[$col];
                    } else {
                        if (!$col_opt) {
                            $this->meth_Misc_Alert('for merging the automatic sub-block ['.$name.']', 'key \''.$col.'\' is not found in record #'.$Src->RecNum.' of block ['.$BDef->Name.']. This key can become optional if you designate it with parenthesis in the main block, i.e.: sub'.$i.'=('.$col.')');
                        }
                        unset($data);
                        $data = array();
                    }
                }
                if (is_string($data)) {
                    $data = explode(',', $data);
                } elseif (is_null($data) || ($data === false)) {
                    $data = array();
                }
                $this->meth_Merge_Block($Txt, $name, $data, $query, false, 0, false);
            }
        }

        return $Txt;
    }
}

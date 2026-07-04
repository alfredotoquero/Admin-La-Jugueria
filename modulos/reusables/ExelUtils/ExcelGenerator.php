<?php

namespace Modulos\Reusables\ExelUtils;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExcelGenerator
{
  protected $spreadsheet;
  protected $sheet;

  public function __construct()
  {
    $this->spreadsheet = new Spreadsheet();
    $this->sheet = $this->spreadsheet->getActiveSheet();
  }

  public function getSheet()
  {
    return $this->sheet;
  }

  public function setReportTitle($title, $mergeRange = 'A1:N1')
  {
    $this->sheet->setCellValue('A1', $title);
    $this->sheet->getStyle('A1')->getFont()->setSize(18)->setBold(true);
    $this->sheet->mergeCells($mergeRange);
    $this->sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
  }

  public function setHeaders(array $headers, $row = 2, $numberAutoWidth = 2)
  {
    $colIndex = 1; // A=1 (Aquí usamos 1 en lugar de 0 para mayor claridad en este ejemplo)
    foreach ($headers as $header) {
      $colLetter = Coordinate::stringFromColumnIndex($colIndex);
      $this->sheet->setCellValue($colLetter . $row, $header);
      if ( $colIndex <= $numberAutoWidth){
        $this->sheet->getColumnDimension($colLetter)->setAutoSize(true);
      } else {
        $this->sheet->getColumnDimension($colLetter)->setWidth(20);
      }
      $colIndex++;
    }
    $lastColLetter = Coordinate::stringFromColumnIndex($colIndex - 1);
    $range = "A{$row}:{$lastColLetter}{$row}";
    $this->sheet->getStyle($range)->getFont()->setBold(true);
    $this->sheet->getStyle($range)->getFont()->getColor()->setRGB('FFFFFF');
    $this->sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)
      ->getStartColor()->setRGB('4F81BD');
  }

  public function setData(array $data, $startingRow = 3)
  {
    $row = $startingRow;
    foreach ($data as $rowData) {
      $colIndex = 1;
      foreach ($rowData as $cellValue) {
        $colLetter = Coordinate::stringFromColumnIndex($colIndex);
        $this->sheet->setCellValue($colLetter . $row, $cellValue);
        $colIndex++;
      }
      $row++;
    }
  }

  public function applyStyle($cellCoordinate, array $styleArray)
  {
    $this->sheet->getStyle($cellCoordinate)->applyFromArray($styleArray);
  }

  public function output($filename = "reporte.xlsx")
  {
    $this->sheet->setTitle('Reporte');
    $this->spreadsheet->setActiveSheetIndex(0);
    $writer = new Xlsx($this->spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');
    exit;
  }
}

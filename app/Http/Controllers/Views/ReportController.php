<?php

namespace App\Http\Controllers\Views;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Report;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index_reportJumlah()
    {
        $report = new Report();
        $title = 'Halaman Report Lunas';
        $rw = explode('/',session()->get('user')->rt_rw)[1];
        // $data = array(
        //     'rw' => $rw,
        //     'bulan' => 'juli',
        //     'tahun' => '2022'
        // );
        $bulan = $this->check_mount(date('m'));
        $tahun = date('Y');
        $data = array(
            'rw' => $rw,
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $report_lunas = $report->lunas_report($data);
        $all_rt = $report->getAllRt($rw);
        $table_rekap = $this->rekap($all_rt,$report_lunas);
        return view('report/report_lunas', compact('title','table_rekap','bulan','tahun'));
    }

    //controllrt untuk tunggakan
    public function index_reportTunggakan()
    {
        $report = new Report();
        $title = 'Halaman Report Tunggakan';
        $rw = explode('/',session()->get('user')->rt_rw)[1];
        $bulan = $this->check_mount(date('m'));
        $tahun = date('Y');
        $data = array(
            'rt_rw' => $rw,
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $tunggakan_report = $report->tunggakan_report($data);
        $all_rt = $report->getAllRt($rw);
        $table_rekap = $this->rekap($all_rt,$tunggakan_report);
        return view('report/report_tunggakan', compact('title','table_rekap','bulan','tahun'));
    }

    public function detail_jumlah($rt_rw, $bulan, $tahun){
        $report = new Report();
        $rt_rw = explode('-',$rt_rw);
        $title = 'Halaman Report Detail RT '.$rt_rw[0].' RW '.$rt_rw[1];
        // $data = array(
        //     'rt_rw' => implode('/',$rt_rw),
        //     'bulan' => 'juli',
        //     'tahun' => '2022'
        // );
        $data = array(
            'rt_rw' => implode('/',$rt_rw),
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $detail_report = $report->detail_lunas($data);
        // dd($detail_report);
        return view('report/detail_reportLunas', compact('title','detail_report'));
    }

    public function detail_tunggakan($rt_rw, $bulan, $tahun){
        $report = new Report();
        $rt_rw = explode('-',$rt_rw);
        $title = 'Halaman Report Detail Tunggakan RT '.$rt_rw[0].' RW '.$rt_rw[1];
        // $data = array(
        //     'rt_rw' => implode('/',$rt_rw),
        //     'bulan' => 'juli',
        //     'tahun' => '2022'
        // );
        $data = array(
            'rt_rw' => implode('/',$rt_rw),
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $detail_report = $report->detail_tunggakan($data);
        // dd($detail_report);
        return view('report/detail_reportTunggakan', compact('title','detail_report'));
    }

    public function ajaxJumlah($tahun,$bulan){
        $report = new Report();
        $title = 'Halaman Report';
        $rw = explode('/',session()->get('user')->rt_rw)[1];
        // $data = array(
        //     'rw' => $rw,
        //     'bulan' => 'juli',
        //     'tahun' => '2022'
        // );
        $data = array(
            'rw' => $rw,
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $report_lunas = $report->lunas_report($data);
        $all_rt = $report->getAllRt($rw);
        $table_rekap = [];
        foreach ($all_rt as $key => $rt) {
            $jumlah = 0;
            foreach ($report_lunas as $key_report => $lunas) {
                if ($rt->rt_rw == $lunas->rt_rw) {
                    $jumlah = $lunas->jumlah;
                    break;
                }
            }
            $table_rekap[$key] = array(
                'rt_rw' => $rt->rt_rw,
                'jumlah' => $jumlah,
            );
        }
        return response()->json($table_rekap);
    }

    public function ajaxTunggakan($tahun,$bulan){
        $report = new Report();
        $title = 'Halaman Report Tunggakan';
        $rw = explode('/',session()->get('user')->rt_rw)[1];
        $data = array(
            'rt_rw' => $rw,
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $tunggakan_report = $report->tunggakan_report($data);
        $all_rt = $report->getAllRt($rw);
        $table_rekap = $this->rekap($all_rt,$tunggakan_report);
        
        return response()->json($table_rekap);
    }

    public function printJumlah($tahun,$bulan){
            $report = new Report();
            $title = 'Halaman Report';
            $rw = explode('/',session()->get('user')->rt_rw)[1];
            $data = array(
                'rw' => $rw,
                'bulan' => $bulan,
                'tahun' => $tahun
            );
            $report_lunas = $report->lunas_report($data);
            $all_rt = $report->getAllRt($rw);
            $table_rekap = [];
            foreach ($all_rt as $key => $rt) {
                $jumlah = 0;
                foreach ($report_lunas as $key_report => $lunas) {
                    if ($rt->rt_rw == $lunas->rt_rw) {
                        $jumlah = $lunas->jumlah;
                        break;
                    }
                }
                $table_rekap[$key] = array(
                    'rt_rw' => $rt->rt_rw,
                    'jumlah' => $jumlah,
                );
            }

            //inisialisasi spreadsheet
            $spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);
            $sheet->mergeCells("A1:I2");
            $sheet->setCellValue('A1', 'Rekap Iuran RW '.explode('/',session()->get('user')->rt_rw)[1].' Bulan '.$bulan." ".$tahun);
            $sheet->getStyle('A:C')->getAlignment()->setHorizontal('center');
            $sheet->getStyle('A:C')->getAlignment()->setVertical('center');
			$sheet->setCellValue('A4', 'No');
			$sheet->setCellValue('B4', 'RT RW');
			$sheet->setCellValue('C4', 'Jumlah');
            $no_cell = 5;
            $no = 1;
            //data spreadsheet
            foreach ($table_rekap as $key => $rekap) {
                $sheet->setCellValue('A'.$no_cell, $no);
                $sheet->setCellValue('B'.$no_cell, $rekap['rt_rw']);
			    $sheet->setCellValue('C'.$no_cell, $rekap['jumlah']);
                $no++;
                $no_cell++;
            }
			$sheet->getStyle('A1')->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            $sheet->getStyle('A1')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            $sheet->getStyle('A1')->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            $sheet->getStyle('A1')->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
			$writer = new Xlsx($spreadsheet);
			$filename = 'Rekap Iuran RW '.explode('/',session()->get('user')->rt_rw)[1].' Bulan '.$bulan." ".$tahun;
			
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'. $filename .'.xlsx"'); 
			header('Cache-Control: max-age=0');
	
            ob_end_clean();
			$writer->save('php://output');
    }

    public function printTunggakan($tahun,$bulan){
        $report = new Report();
        $rw = explode('/',session()->get('user')->rt_rw)[1];
        $bulan = $this->check_mount(date('m'));
        $tahun = date('Y');
        $data = array(
            'rt_rw' => $rw,
            'bulan' => $bulan,
            'tahun' => $tahun
        );
        $tunggakan_report = $report->tunggakan_report($data);
        $all_rt = $report->getAllRt($rw);
        $table_rekap = $this->rekap($all_rt,$tunggakan_report);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'RT RW');
        $sheet->setCellValue('C1', 'Jumlah');
        $no = 2;
        foreach ($table_rekap as $key => $rekap) {
            $sheet->setCellValue('A'.$no, $no-1);
            $sheet->setCellValue('B'.$no, $rekap['rt_rw']);
            $sheet->setCellValue('C'.$no, $rekap['jumlah']);
            $no++;
        }
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-siswa';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'. $filename .'.xlsx"'); 
        header('Cache-Control: max-age=0');

        ob_end_clean();
        $writer->save('php://output');
    }

    public function detailJumlah(){

    }

    function check_mount($bulan){
        switch ($bulan) {
            case '01':
                return 'januari';
                break;
            case '02':
                return 'februari';
                break;
            case '03':
                return 'maret';
                break;
            case '04':
                return 'april';
                break;
            case '05':
                return 'mei';
                break;
            case '06':
                return 'juni';
                break;
            case '07':
                return 'juli';
                break;
            case '08':
                return 'agustus';
                break;
            case '09':
                return 'september';
                break;
            case '10':
                return 'oktober';
                break;
            case '11':
                return 'november';
                break;
            case '12':
                return 'desember';
                break;
            default:
                break;
        }
    }

    function rekap($all_rt,$report){
        $table_rekap = [];
        foreach ($all_rt as $key => $rt) {
            $jumlah = 0;
            foreach ($report as $key_report => $value) {
                if ($rt->rt_rw == $value->rt_rw) {
                    $jumlah = $value->jumlah;
                    break;
                }
            }
            $table_rekap[$key] = array(
                'rt_rw' => $rt->rt_rw,
                'jumlah' => $jumlah,
            );
        }
        return $table_rekap;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

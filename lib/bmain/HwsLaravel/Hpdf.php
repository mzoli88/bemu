<?php

namespace hws;

use Mpdf\Mpdf;

class Hpdf
{
    static function create($html, $header = null, $footer = null, $download = false, array $options = [
        'format' => 'A4',
        // 'orientation' => 'L' //vagy 'P'
        // 'margin_left' => 20,
        // 'margin_right' => 20,
        // 'margin_top' => 20,
        // 'margin_bottom' => 20,
        // 'margin_header' => 5,
        // 'margin_footer' => 5,
    ])
    {
        // példa a margó beállításra
        // if(!empty($model->margin_header) || $model->margin_header == 0)$op['margin_top'] = $model->margin_header;
        // if(!empty($model->margin_footer) || $model->margin_footer == 0)$op['margin_bottom'] = $model->margin_footer;
        // if(!empty($model->margin_content || $model->margin_content == 0)){
        //     $op['margin_left'] = $model->margin_content;
        //     $op['margin_right'] = $model->margin_content;
        // }

        $mpdf = new Mpdf($options);

        $image_index = 0;

        //{PAGENO}/{nbpg}
        if (!empty($header)) {
            preg_match_all('/src="[^"]+"/', $header, $match);
            $match = collect($match[0])->map(function ($x) {
                return preg_replace(['/src="/', '/"$/'], '', $x);
            })->each(function ($img) use (&$image_index, &$header, $mpdf) {
                $image_index++;
                $mpdf->imageVars['Img_' . $image_index] = file_get_contents($img);
                $header = str_replace($img, 'var:Img_' . $image_index, $header);
            });
            $mpdf->SetHTMLHeader($header);
        }

        if (!empty($footer)) {
            preg_match_all('/src="[^"]+"/', $footer, $match);
            $match = collect($match[0])->map(function ($x) {
                return preg_replace(['/src="/', '/"$/'], '', $x);
            })->each(function ($img) use (&$image_index, &$footer, $mpdf) {
                $image_index++;
                $mpdf->imageVars['Img_' . $image_index] = file_get_contents($img);
                $footer = str_replace($img, 'var:Img_' . $image_index, $footer);
            });
            $mpdf->SetHTMLFooter($footer);
        }

        $mpdf->WriteHTML($html);
        // $mpdf->showImageErrors = true;

        if ($download) {
            if (is_string($download)) {
                $download = preg_replace('/\s+/', '_', $download);
                $download = preg_replace("/[^a-zA-Z0-9\_íÍéÉáÁűŰőŐúÚöÖüÜóÓ]+/", "", $download);
                $mpdf->Output($download . '.pdf', 'D');
            } else {
                return $mpdf->OutputHttpInline();
            }
        } else {
            return $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
        }
    }
}

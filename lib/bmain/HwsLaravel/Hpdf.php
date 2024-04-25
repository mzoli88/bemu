<?php

namespace hws;

use Mpdf\Mpdf;

class Hpdf
{


    static function getTag(&$string, $tagname)
    {
        $pattern = "#<\s*?$tagname\b[^>]*>(.*?)</$tagname\b[^>]*>#s";
        preg_match($pattern, $string, $matches);
        $string = preg_replace($pattern, '', $string);
        return array_key_exists(1, $matches) ? $matches[1] : '';
    }

    static function create($html, $download = false, $header = null, $footer = null, array $options = [
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

        // <pdfFormat>A5</pdfFormat>

        $format = self::getTag($html, 'pdfFormat');
        if (!empty($format)) $options['format'] = $format;

        if (empty($options['orientation'] ?? null)) {
            $tmp = self::getTag($html, 'pdfOrientation');
            if (!empty($tmp)) $options['orientation'] = $tmp;
        }

        if (empty($options['margin_left'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginLeft');
            if (!empty($tmp) || $tmp === '0') $options['margin_left'] = $tmp;
        }

        if (empty($options['margin_right'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginRight');
            if (!empty($tmp) || $tmp === '0') $options['margin_right'] = $tmp;
        }

        if (empty($options['margin_top'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginTop');
            if (!empty($tmp) || $tmp === '0') $options['margin_top'] = $tmp;
        }

        if (empty($options['margin_bottom'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginBottom');
            if (!empty($tmp) || $tmp === '0') $options['margin_bottom'] = $tmp;
        }

        if (empty($options['margin_header'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginHeader');
            if (!empty($tmp) || $tmp === '0') $options['margin_header'] = $tmp;
        }

        if (empty($options['margin_footer'] ?? null)) {
            $tmp = self::getTag($html, 'pdfMarginFooter');
            if (!empty($tmp) || $tmp === '0') $options['margin_footer'] = $tmp;
        }

        $mpdf = new Mpdf($options);

        $image_index = 0;

        if (empty($header)) $header = self::getTag($html, 'pdfHeader');

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

        if (empty($footer)) $header = self::getTag($html, 'pdfFooter');

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

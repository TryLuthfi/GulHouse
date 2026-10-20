<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('contact');
        $this->load->model('Landing_model');
    }

    public function index()
    {
        $data = array(
            'title' => 'GUL HOUSE - Kos Premium Siap Huni',
            'summary' => $this->Landing_model->get_summary(),
            'room_types' => $this->Landing_model->get_room_types(),
            'featured_rooms' => $this->Landing_model->get_featured_rooms(),
            'all_rooms' => $this->Landing_model->get_all_public_rooms(),
            'property_stats' => $this->Landing_model->get_property_stats(),
            'amenities' => $this->Landing_model->get_amenities(),
            'testimonials' => $this->Landing_model->get_testimonials(),
            'cost_items' => $this->Landing_model->get_cost_items(),
            'house_rules' => $this->Landing_model->get_house_rules(),
            'nearby_places' => $this->Landing_model->get_nearby_places(),
            'hero_slides' => $this->get_slider_images(),
            'preview_photos' => $this->Landing_model->get_preview_photos(4),
            'daily_living_photo' => $this->Landing_model->get_daily_living_photo(),
        );

        $this->load->view('landing_home', $data);
    }

    public function slider($filename = null)
    {
        $filename = basename(rawurldecode((string) $filename));
        $path = $this->slider_path($filename);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        );

        if (! $filename || ! isset($mimeTypes[$extension]) || ! is_file($path)) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type($mimeTypes[$extension])
            ->set_header('Cache-Control: public, max-age=604800')
            ->set_output(file_get_contents($path));
    }

    public function logo()
    {
        $path = realpath(FCPATH . '../uploads/GH LOGO.png');

        if (! $path || ! is_file($path)) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type('image/png')
            ->set_header('Cache-Control: public, max-age=604800')
            ->set_output(file_get_contents($path));
    }

    public function gallery($property = null, $type = null, $filename = null)
    {
        $property = strtoupper(trim((string) $property));
        $type = strtoupper(trim((string) $type));
        $filename = basename(rawurldecode((string) $filename));
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mimeTypes = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        );

        if ($property === '' || $type === '' || ! isset($mimeTypes[$extension])) {
            show_404();
            return;
        }

        $directory = realpath(FCPATH . '../uploads/' . $this->folder_key($property) . '/' . $this->folder_key($type));
        $path = $directory ? realpath($directory . DIRECTORY_SEPARATOR . $filename) : FALSE;

        if (! $directory || ! $path || strpos($path, $directory) !== 0 || ! is_file($path)) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type($mimeTypes[$extension])
            ->set_header('Cache-Control: public, max-age=604800')
            ->set_output(file_get_contents($path));
    }

    public function booking()
    {
        $payload=array();$limits=array('fullname'=>150,'phone'=>64,'room_interest'=>150,'move_in_plan'=>60,'message'=>2000);
        foreach($limits as $field=>$limit){$value=$this->input->post($field,TRUE);$payload[$field]=is_scalar($value)?trim((string)$value):'';}

        $saved = $this->Landing_model->save_booking_request($payload);
        if($saved)$this->session->unset_userdata(array('booking_error','booking_error_field','booking_old'));
        else {
            $old=array();foreach($limits as $field=>$limit)$old[$field]=mb_substr($payload[$field],0,$limit);
            $this->session->set_flashdata('booking_error',$this->Landing_model->get_booking_error()?:'Permintaan belum dapat disimpan. Periksa data booking lalu coba lagi.');
            $this->session->set_flashdata('booking_error_field',$this->Landing_model->get_booking_error_field()?:'form');
            $this->session->set_flashdata('booking_old',$old);
        }
        $this->session->set_flashdata($saved ? 'success' : 'warning', $saved
            ? 'Terima kasih. Permintaan booking sudah kami terima.'
            : 'Permintaan belum tersimpan ke database, silakan hubungi WhatsApp GUL HOUSE.');

        redirect(base_url('#booking'));
    }

    public function payment()
    {
        $this->load->model('Resident_access_model');
        $this->output->set_header('Cache-Control: no-store');
        redirect(base_url($this->Resident_access_model->current()?'resident/portal#tagihan':'resident/access'),'location',303);
    }

    public function payment_check()
    {
        // Old HP/NIK form submissions never establish a payment session.
        $this->session->unset_userdata('gh_payment_access');
        $this->payment();
    }

    public function payment_status()
    {
        $this->payment();
    }

    public function complaint_submit()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_error('Gunakan formulir komplain.', 405); return; }
        $this->load->model('Resident_access_model');$case=$this->Resident_access_model->current();
        if (!$case || empty($case['tenant_id']) || empty($case['stay_id'])) { redirect(base_url('resident/access'),'location',303);return; }
        $context = $this->Landing_model->get_payment_context((int)$case['tenant_id'], (int)$case['stay_id']);
        if (!$context || $context['stay_status'] !== 'active') { show_error('Komplain tersedia untuk penghuni aktif.',403); return; }
        $payload = $this->input->post(NULL, TRUE);
        $ok = $this->Landing_model->submit_resident_complaint($context, is_array($payload) ? $payload : array());
        $this->session->set_flashdata($ok ? 'portal_success' : 'portal_error', $ok ? 'Komplain diterima. Pengelola akan menindaklanjuti tiket ini.' : 'Komplain belum tersimpan. Periksa judul dan detail.');
        redirect(base_url('resident/portal#tagihan'),'location',303);
    }

    public function payment_logout()
    {
        $this->load->model('Resident_access_model');$this->Resident_access_model->clear();
        $this->session->sess_regenerate(TRUE);
        redirect(base_url('resident/access'),'location',303);
    }

    public function house_rules()
    {
        $template = $this->Landing_model->get_house_rules_document();
        if (! $template) {
            show_404();
            return;
        }
        $pdf = is_file($template['pdf_path']) ? file_get_contents($template['pdf_path'])
            : $this->build_house_rules_pdf(preg_split('/\r\n|\r|\n/', trim($template['body_template'])));

        $this->output
            ->set_content_type('application/pdf')
            ->set_header('X-Content-Type-Options: nosniff')
            ->set_header('Content-Disposition: inline; filename="house_rules.pdf"')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
            ->set_header('Pragma: no-cache')
            ->set_output($pdf);
    }

    public function room($slug = null)
    {
        if (is_numeric($slug)) {
            redirect(base_url('#rooms'));
            return;
        }

        $room = $this->Landing_model->get_room_type_detail((string) $slug);

        if ( ! $room) {
            show_404();
            return;
        }

        $data = array(
            'title' => $room['name'] . ' | GUL HOUSE',
            'room' => $room,
            'gallery' => $this->Landing_model->get_room_gallery($room),
            'similar_rooms' => $this->Landing_model->get_similar_room_types($room),
        );

        $this->load->view('room_detail', $data);
    }

    private function get_slider_images()
    {
        $directory = realpath(FCPATH . '../uploads/SLIDER');
        $slides = array();
        $allowed = array('jpg', 'jpeg', 'png', 'webp');

        if (! $directory || ! is_dir($directory)) {
            return $this->fallback_slider_images();
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, $allowed, TRUE)) {
                continue;
            }

            $filename = $file->getFilename();
            $slides[] = array(
                'filename' => $filename,
                'url' => base_url('index.php/slider/' . rawurlencode($filename)),
            );
        }

        usort($slides, function ($left, $right) {
            return strnatcasecmp($left['filename'], $right['filename']);
        });

        return $slides ? array_values($slides) : $this->fallback_slider_images();
    }

    private function slider_path($filename)
    {
        $directory = realpath(FCPATH . '../uploads/SLIDER');

        if (! $directory) {
            return '';
        }

        return $directory . DIRECTORY_SEPARATOR . basename((string) $filename);
    }

    private function folder_key($value)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));
    }

    private function build_house_rules_pdf(array $rules)
    {
        $content = array(
            'q',
            '1 1 1 rg 0 0 595 842 re f',
            '0.04 0.09 0.12 rg',
            'BT',
            '/F2 24 Tf 72 770 Td (GUL HOUSE) Tj',
            '/F1 11 Tf 0 -20 Td (Dokumen house_rules) Tj',
            '/F2 18 Tf 0 -46 Td (Aturan Tinggal) Tj',
            '/F1 11 Tf 0 -22 Td (Ringkasan aturan berikut menjadi acuan calon penghuni sebelum booking dan check-in.) Tj',
            'ET',
        );

        $y = 670;
        $number = 1;
        foreach ($rules as $rule) {
            $lines = $this->wrap_pdf_text((string) $rule, 78);
            $firstLine = true;

            foreach ($lines as $line) {
                $prefix = $firstLine ? ($number . '. ') : '   ';
                $content[] = 'BT /F1 11 Tf 92 ' . $y . ' Td (' . $this->escape_pdf_text($prefix . $line) . ') Tj ET';
                $y -= 18;
                $firstLine = false;
            }

            $y -= 8;
            $number++;
        }

        $content[] = 'BT /F1 9 Tf 72 82 Td (Dokumen ini digenerate otomatis dari template house_rules GUL HOUSE.) Tj ET';
        $content[] = 'Q';

        $stream = implode("\n", $content);
        return $this->compose_pdf($stream);
    }

    private function compose_pdf($stream)
    {
        $objects = array();
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = array(0);

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function wrap_pdf_text($text, $limit)
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return array('');
        }

        return explode("\n", wordwrap($text, $limit, "\n", true));
    }

    private function escape_pdf_text($text)
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
        return str_replace(array('\\', '(', ')'), array('\\\\', '\\(', '\\)'), $text);
    }

    private function fallback_slider_images()
    {
        return array(
            array('filename' => 'fallback-1', 'url' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1500&q=76'),
            array('filename' => 'fallback-2', 'url' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1500&q=76'),
            array('filename' => 'fallback-3', 'url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1500&q=76'),
        );
    }
}

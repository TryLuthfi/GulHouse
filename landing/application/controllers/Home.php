<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
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

        if ($property !== 'GH2' || $type !== 'VIP' || ! isset($mimeTypes[$extension])) {
            show_404();
            return;
        }

        $directory = realpath(FCPATH . '../uploads/GH2/VIP');
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
        $payload = array(
            'fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'phone' => trim((string) $this->input->post('phone', TRUE)),
            'room_interest' => trim((string) $this->input->post('room_interest', TRUE)),
            'move_in_plan' => trim((string) $this->input->post('move_in_plan', TRUE)),
            'message' => trim((string) $this->input->post('message', TRUE)),
        );

        $saved = $this->Landing_model->save_booking_request($payload);
        $this->session->set_flashdata($saved ? 'success' : 'warning', $saved
            ? 'Terima kasih. Permintaan booking sudah kami terima.'
            : 'Permintaan belum tersimpan ke database, silakan hubungi WhatsApp GUL HOUSE.');

        redirect(base_url('#booking'));
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

    private function fallback_slider_images()
    {
        return array(
            array('filename' => 'fallback-1', 'url' => 'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1500&q=76'),
            array('filename' => 'fallback-2', 'url' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1500&q=76'),
            array('filename' => 'fallback-3', 'url' => 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1500&q=76'),
        );
    }
}

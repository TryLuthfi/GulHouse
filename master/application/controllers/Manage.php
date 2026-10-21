<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH.'libraries/Mobile_command_bridge.php';
require_once APPPATH.'libraries/Gallery_cover.php';
require_once APPPATH.'libraries/Gallery_focus.php';
require_once APPPATH.'libraries/Landing_photos.php';

class Manage extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->require_login();
        $this->load->model('Master_model');
    }

    public function properties()
    {
        $id = (int) $this->input->get('id');
        $filters = array(
            'type' => trim((string) $this->input->get('type', TRUE)),
            'status' => trim((string) $this->input->get('status', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
        );

        $this->load->view('manage/properties', array(
            'title' => 'Properti | GUL HOUSE',
            'region_data' => $this->Master_model->get_region_catalog(),
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_properties($filters),
            'filters' => $filters,
            'edit' => $id > 0 ? $this->Master_model->get_property($id) : null,
        ));
    }

    public function save_property()
    {
        foreach (array('city','address') as $field) {
            if ($this->input->post($field)!==NULL && !is_string($this->input->post($field))) { $this->master_error('properties','Alamat atau kota tidak valid.');return; }
        }
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'code' => strtoupper(trim((string) $this->input->post('code', TRUE))),
            'name' => trim((string) $this->input->post('name', TRUE)),
            'property_type' => trim((string) $this->input->post('property_type', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)),
            'city' => trim((string) $this->input->post('city', TRUE)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        // Older mobile clients do not send city; retain their current master value.
        if ($this->input->post('city') === NULL) {
            $existing = $payload['id'] > 0 ? $this->Master_model->get_property($payload['id']) : array();
            $payload['city'] = (string) ($existing['city'] ?? '');
        }
        if ($this->input->post('address') === NULL) {
            $existing = $payload['id'] > 0 ? $this->Master_model->get_property($payload['id']) : array();
            $payload['address']=(string)($existing['address']??'');
        }
        foreach (Gulhouse_region_location::fields() as $field) if ($this->input->post($field)!==NULL) $payload[$field]=$this->input->post($field);

        if (mb_strlen($payload['city']) > 120) {
            $this->master_error('properties', 'Kota maksimal 120 karakter.'); return;
        }
        if (! in_array($payload['property_type'], array('house', 'apartment', 'other'), TRUE)) {
            $this->master_error('properties', 'Jenis properti tidak valid. Pilih House, Apartment, atau Other.'); return;
        }
        if ($payload['code'] === '' || strlen($payload['code']) > 30 || $payload['name'] === '' || mb_strlen($payload['name']) > 120
            || $this->db->where('code', $payload['code'])->where('id !=', $payload['id'])->count_all_results('gh_properties')) {
            $this->master_error('properties', 'Kode properti harus unik (maksimal 30 karakter), nama wajib diisi (maksimal 120 karakter).'); return;
        }
        if (! $payload['is_active'] && $this->has_enabled_rooms('property_id', $payload['id'])) {
            $this->master_error('properties', 'Arsipkan kamar terlebih dahulu sebelum menonaktifkan properti.'); return;
        }
        $ok = $this->Master_model->save_property($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti tersimpan.' : ($this->Master_model->get_location_error() ?: 'Properti belum tersimpan. Cek kode dan nama.'));
        gh_mobile_redirect('properties', 'location', 303);
    }

    public function delete_property($id)
    {
        if ($this->has_enabled_rooms('property_id', (int) $id)) {
            $this->master_error('properties', 'Arsipkan kamar terlebih dahulu sebelum menonaktifkan properti.'); return;
        }
        $ok = $this->Master_model->soft_delete_property((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti dinonaktifkan.' : 'Properti belum bisa dinonaktifkan.');
        gh_mobile_redirect('properties', 'location', 303);
    }

    public function room_types()
    {
        $id = (int) $this->input->get('id');
        $filters = array(
            'property_type' => trim((string) $this->input->get('property_type', TRUE)),
            'status' => trim((string) $this->input->get('status', TRUE)),
            'usage' => trim((string) $this->input->get('usage', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
        );

        $this->load->view('manage/room_types', array(
            'title' => 'Tipe Kamar | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_room_types($filters),
            'filters' => $filters,
            'edit' => $id > 0 ? $this->Master_model->get_room_type($id) : null,
        ));
    }

    public function save_room_type()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'name' => trim((string) $this->input->post('name', TRUE)),
            'description' => trim((string) $this->input->post('description', TRUE)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        if ($payload['name'] === '' || mb_strlen($payload['name']) > 80
            || $this->db->where('name', $payload['name'])->where('id !=', $payload['id'])->count_all_results('gh_room_types')) {
            $this->master_error('room-types', 'Nama tipe harus unik dan diisi maksimal 80 karakter.'); return;
        }
        if (! $payload['is_active'] && $this->has_enabled_rooms('room_type_id', $payload['id'])) {
            $this->master_error('room-types', 'Arsipkan kamar terlebih dahulu sebelum menonaktifkan tipe.'); return;
        }
        $ok = $this->Master_model->save_room_type($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar tersimpan.' : 'Tipe kamar belum tersimpan.');
        gh_mobile_redirect('room-types', 'location', 303);
    }

    public function delete_room_type($id)
    {
        if ($this->has_enabled_rooms('room_type_id', (int) $id)) {
            $this->master_error('room-types', 'Arsipkan kamar terlebih dahulu sebelum menonaktifkan tipe.'); return;
        }
        $ok = $this->Master_model->soft_delete_room_type((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar dinonaktifkan.' : 'Tipe kamar belum bisa dinonaktifkan.');
        gh_mobile_redirect('room-types', 'location', 303);
    }

    public function rooms()
    {
        $id = (int) $this->input->get('id');
        $filters = array(
            'property_id' => (int) $this->input->get('property_id'),
            'room_type_id' => (int) $this->input->get('room_type_id'),
            'status' => trim((string) $this->input->get('status', TRUE)),
            'public' => trim((string) $this->input->get('public', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
        );

        $this->load->view('manage/rooms', array(
            'title' => 'Kamar | GUL HOUSE',
            'region_data' => $this->Master_model->get_region_catalog(),
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_rooms($filters),
            'room_catalog' => $this->Master_model->get_rooms(),
            'properties' => $this->Master_model->get_active_properties(),
            'room_types' => $this->Master_model->get_active_room_types(),
            'filters' => $filters,
            'edit' => $id > 0 ? $this->Master_model->get_room($id) : null,
        ));
    }

    public function save_room()
    {
        foreach (array('unit_city','unit_address') as $locationField) {
            $value = $this->input->post($locationField, TRUE);
            if ($value !== NULL && !is_string($value)) { $this->master_error('rooms','Lokasi unit tidak valid.'); return; }
        }
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'property_id' => (int) $this->input->post('property_id'),
            'room_type_id' => (int) $this->input->post('room_type_id'),
            'room_code' => strtoupper(trim((string) $this->input->post('room_code', TRUE))),
            'room_name' => trim((string) $this->input->post('room_name', TRUE)),
            'room_label' => trim((string) $this->input->post('room_label', TRUE)),
            'unit_city' => trim((string) $this->input->post('unit_city', TRUE)),
            'unit_address' => trim((string) $this->input->post('unit_address', TRUE)),
            'floor_no' => $this->nullable_int($this->input->post('floor_no')),
            'deposit' => $this->nullable_money($this->input->post('deposit')),
            'monthly_price' => $this->nullable_money($this->input->post('monthly_price')),
            'status' => trim((string) $this->input->post('status', TRUE)),
            'is_public' => $this->input->post('is_public') ? 1 : 0,
        );

        // Existing mobile clients omit location fields; retain values already saved.
        $currentLocation = $payload['id'] > 0 ? $this->Master_model->get_room($payload['id']) : array();
        foreach (array('unit_city','unit_address') as $locationField) {
            if ($this->input->post($locationField) === NULL) $payload[$locationField] = (string) ($currentLocation[$locationField] ?? '');
        }
        if (mb_strlen($payload['unit_city']) > 120 || mb_strlen($payload['unit_address']) > 2000) {
            $this->master_error('rooms','Kota unit maksimal 120 karakter dan alamat unit maksimal 2.000 karakter.'); return;
        }
        $property = $this->db->where('id', $payload['property_id'])->where('is_active', 1)->get('gh_properties')->row_array();
        $type = $this->db->where('id', $payload['room_type_id'])->where('is_active', 1)->get('gh_room_types')->row_array();
        if (! $property || ! $type || $payload['room_code'] === '' || strlen($payload['room_code']) > 30
            || mb_strlen($payload['room_name']) > 100 || mb_strlen($payload['room_label']) > 150
            || ($payload['floor_no'] !== NULL && ($payload['floor_no'] < 0 || $payload['floor_no'] > 255))
            || $this->db->where('property_id', $payload['property_id'])->where('room_code', $payload['room_code'])->where('id !=', $payload['id'])->count_all_results('gh_rooms')) {
            $this->master_error('rooms', 'Kode kamar harus unik pada properti aktif. Periksa tipe, panjang teks, dan lantai (0–255).'); return;
        }
        if ($payload['id'] && $this->has_open_stay($payload['id'])) {
            $current = $this->Master_model->get_room($payload['id']);
            if (! $current || $payload['status'] !== $current['status'] || $payload['property_id'] !== (int) $current['property_id'] || $payload['room_type_id'] !== (int) $current['room_type_id']) {
                $this->master_error('rooms', 'Kamar memiliki masa tinggal berjalan. Ubah status melalui alur kontrak/check-out terlebih dahulu.'); return;
            }
        }
        foreach (Gulhouse_region_location::fields() as $field) if ($this->input->post($field)!==NULL) $payload[$field]=$this->input->post($field);
        $ok = $this->Master_model->save_room($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar tersimpan.' : ($this->Master_model->get_location_error() ?: 'Kamar belum tersimpan.'));
        gh_mobile_redirect('rooms', 'location', 303);
    }

    public function delete_room($id)
    {
        if ($this->has_open_stay((int) $id)) {
            $this->master_error('rooms', 'Kamar memiliki masa tinggal berjalan. Selesaikan check-out terlebih dahulu.'); return;
        }
        $ok = $this->Master_model->soft_delete_room((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar diarsipkan.' : 'Kamar belum bisa diarsipkan.');
        gh_mobile_redirect('rooms', 'location', 303);
    }

    public function toggle_room_public($id)
    {
        $this->Master_model->toggle_room_public((int) $id);
        gh_mobile_redirect('rooms', 'location', 303);
    }

    public function photos()
    {
        $selectedProperty = strtoupper(trim((string) $this->input->get('property', TRUE)));
        $selectedType = trim((string) $this->input->get('type', TRUE));
        $selectedCategory = trim((string) $this->input->get('category', TRUE));
        $properties = $this->Master_model->get_active_properties();
        $roomTypes = $this->Master_model->get_active_room_types();
        $categories = $this->photo_categories();
        $selectedProperty = $this->resolve_photo_value($selectedProperty, $properties, 'code');
        $selectedType = $this->resolve_photo_value($selectedType, $roomTypes, 'name');
        $roomPhotos = $this->list_room_photos($selectedProperty, $selectedType);
        $selectedCategory = $this->resolve_photo_category($selectedCategory, $categories);
        $categoryCounts = array('all' => count($roomPhotos));

        foreach ($categories as $label) {
            $categoryCounts[$label] = 0;
        }

        foreach ($roomPhotos as $photo) {
            $label = isset($photo['category']) ? (string) $photo['category'] : 'Kamar';
            if (! isset($categoryCounts[$label])) {
                $categoryCounts[$label] = 0;
            }
            $categoryCounts[$label]++;
        }

        $landingChoices=array();
        foreach(Gulhouse_landing_photos::catalog($this->uploads_path('')) as $choice) {
            $choice['src']=base_url('photos/view?'.http_build_query(array('scope'=>'room','property'=>$choice['property'],'type'=>$choice['type'],'file'=>$choice['file'])));
            $landingChoices[]=$choice;
        }
        $this->load->view('manage/photos', array(
            'landing_photo_choices'=>$landingChoices,
            'landing_photo_selection'=>Gulhouse_landing_photos::read($this->uploads_path('')),
            'title' => 'Foto | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'properties' => $properties,
            'room_types' => $roomTypes,
            'room_catalog' => $this->Master_model->get_rooms_for_payment_filters(),
            'selected_property' => $selectedProperty,
            'selected_type' => $selectedType,
            'selected_category' => $selectedCategory,
            'slider_photos' => $this->list_slider_photos(),
            'room_photos' => $roomPhotos,
            'room_photo_groups' => $this->list_room_photo_groups(),
            'categories' => $categories,
            'category_counts' => $categoryCounts,
        ));
    }

    public function save_landing_photos()
    {
        $ok=Gulhouse_landing_photos::save($this->uploads_path(''),$this->input->post('preview'),$this->input->post('daily'));
        $this->session->set_flashdata($ok?'success':'error',$ok?'Foto Landing tersimpan.':'Pilih empat foto Preview yang berbeda dan satu foto Daily living dari galeri yang tersedia.');
        gh_mobile_redirect('photos','location',303);
    }

    public function upload_slider_photos()
    {
        $result = $this->store_uploaded_photos('photos', $this->uploads_path('SLIDER'), 'slider');
        $this->session->set_flashdata($result['ok'] ? 'success' : 'error', $result['message']);
        gh_mobile_redirect('photos', 'location', 303);
    }

    public function upload_room_photos()
    {
        $rows = $this->input->post('upload_rows', TRUE);
        if (! is_array($rows)) {
            $rows = array(array(
                'property_code' => $this->input->post('property_code', TRUE),
                'room_type' => $this->input->post('room_type', TRUE),
                'category' => $this->input->post('category', TRUE),
                'field' => 'photos',
            ));
        }

        $savedTotal = 0;
        $failedTotal = 0;
        $lastProperty = '';
        $lastType = '';

        foreach ($rows as $index => $row) {
            $property = trim((string) (isset($row['property_code']) ? $row['property_code'] : ''));
            $type = trim((string) (isset($row['room_type']) ? $row['room_type'] : ''));
            $category = trim((string) (isset($row['category']) ? $row['category'] : ''));
            $field = isset($row['field']) ? trim((string) $row['field']) : 'photos_' . (int) $index;
            $propertyFolder = $this->folder_key($property);
            $typeFolder = $this->folder_key($type);

            if ($propertyFolder === '' || $typeFolder === '' || ! isset($this->photo_categories()[$category])) {
                $failedTotal++;
                continue;
            }

            $result = $this->store_uploaded_photos($field, $this->uploads_path($propertyFolder . '/' . $typeFolder), $category);
            if ($result['ok']) {
                $savedTotal += (int) (isset($result['saved']) ? $result['saved'] : 0);
                $lastProperty = $property;
                $lastType = $type;
            } else {
                $failedTotal++;
            }

            $failedTotal += (int) (isset($result['failed']) ? $result['failed'] : 0);
        }

        if ($savedTotal === 0) {
            $this->session->set_flashdata('error', 'Foto belum tersimpan. Pastikan setiap row sudah lengkap dan file berformat JPG, JPEG, PNG, atau WEBP.');
            gh_mobile_redirect('photos', 'location', 303);
            return;
        }

        $message = $savedTotal . ' foto berhasil disimpan dari batch upload.';
        if ($failedTotal > 0) {
            $message .= ' ' . $failedTotal . ' row/file dilewati.';
        }

        $this->session->set_flashdata('success', $message);
        gh_mobile_redirect($lastProperty && $lastType ? 'photos?property=' . rawurlencode($lastProperty) . '&type=' . rawurlencode($lastType) : 'photos', 'location', 303);
    }

    public function set_photo_cover()
    {
        $property = $this->input->post('property', TRUE);
        $type = $this->input->post('type', TRUE);
        $file = $this->input->post('file', TRUE);
        if (! is_string($property) || ! is_string($type) || ! is_string($file)) {
            show_error('Pilihan foto tidak valid.', 400); return;
        }
        $property = $this->resolve_photo_value(trim($property), $this->Master_model->get_active_properties(), 'code');
        $type = $this->resolve_photo_value(trim($type), $this->Master_model->get_active_room_types(), 'name');
        $catalog = $this->Master_model->get_rooms_for_payment_filters();
        $validPair = FALSE;
        foreach ($catalog as $room) {
            if ($room['property_code'] === $property && $room['type_name'] === $type) { $validPair = TRUE; break; }
        }
        if ($property === '' || $type === '' || ! $validPair) {
            show_error('Gedung dan tipe kamar tidak valid.', 400); return;
        }
        $directory = $this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type));
        $root = realpath($this->uploads_path(''));
        $resolved = realpath($directory);
        if (! $root || ! $resolved || strpos($resolved, $root . DIRECTORY_SEPARATOR) !== 0
            || is_link($directory) || is_link(dirname($directory))) {
            show_error('Galeri foto tidak valid.', 400); return;
        }
        $ok = Gulhouse_gallery_cover::save($directory, $file);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Foto utama diperbarui untuk kartu landing dan galeri kamar.' : 'Foto utama belum tersimpan. Pastikan foto masih tersedia pada galeri yang dipilih.');
        gh_mobile_redirect('photos?property=' . rawurlencode($property) . '&type=' . rawurlencode($type), 'location', 303);
    }

    public function save_photo_focus()
    {
        $property=$this->input->post('property',TRUE);$type=$this->input->post('type',TRUE);$file=$this->input->post('file',TRUE);
        if(!is_string($property)||!is_string($type)||!is_string($file)){show_error('Pilihan foto tidak valid.',400);return;}
        $property=$this->resolve_photo_value(trim($property),$this->Master_model->get_active_properties(),'code');
        $type=$this->resolve_photo_value(trim($type),$this->Master_model->get_active_room_types(),'name');
        $valid=false;
        foreach($this->Master_model->get_rooms_for_payment_filters() as $room)if($room['property_code']===$property&&$room['type_name']===$type){$valid=true;break;}
        $directory=$this->uploads_path($this->folder_key($property).'/'.$this->folder_key($type));
        $root=realpath($this->uploads_path(''));$resolved=realpath($directory);
        if(!$valid||!$root||!$resolved||strpos($resolved,$root.DIRECTORY_SEPARATOR)!==0||is_link($directory)||is_link(dirname($directory))){show_error('Galeri foto tidak valid.',400);return;}
        $ok=Gulhouse_gallery_focus::save($directory,$file,$this->input->post('focus_x'),$this->input->post('focus_y'));
        $this->session->set_flashdata($ok?'success':'error',$ok?'Posisi fokus foto tersimpan.':'Posisi fokus belum tersimpan. Gunakan angka bulat 0–100 dan foto yang tersedia.');
        gh_mobile_redirect('photos?property='.rawurlencode($property).'&type='.rawurlencode($type),'location',303);
    }

    public function delete_photo()
    {
        $scope = trim((string) $this->input->get_post('scope', TRUE));
        $property = trim((string) $this->input->get_post('property', TRUE));
        $type = trim((string) $this->input->get_post('type', TRUE));
        $file = basename(rawurldecode((string) $this->input->get_post('file', TRUE)));
        $directory = $scope === 'slider'
            ? $this->uploads_path('SLIDER')
            : $this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type));
        $path = $this->safe_upload_file($directory, $file);

        if (! $path || ! is_file($path)) {
            $this->session->set_flashdata('error', 'Foto tidak ditemukan.');
            gh_mobile_redirect('photos', 'location', 303);
            return;
        }

        $ok = unlink($path);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Foto berhasil dihapus.' : 'Foto belum bisa dihapus.');
        gh_mobile_redirect($scope === 'slider' ? 'photos' : 'photos?property=' . rawurlencode($property) . '&type=' . rawurlencode($type), 'location', 303);
    }

    public function view_photo()
    {
        $scope = trim((string) $this->input->get('scope', TRUE));
        $property = trim((string) $this->input->get('property', TRUE));
        $type = trim((string) $this->input->get('type', TRUE));
        $file = basename(rawurldecode((string) $this->input->get('file', TRUE)));
        $directory = $scope === 'slider'
            ? $this->uploads_path('SLIDER')
            : $this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type));
        $path = $this->safe_upload_file($directory, $file);
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimeTypes = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp');

        if (! $path || ! is_file($path) || ! isset($mimeTypes[$extension])) {
            show_404();
            return;
        }

        $this->output
            ->set_content_type($mimeTypes[$extension])
            ->set_header('Cache-Control: public, max-age=604800')
            ->set_output(file_get_contents($path));
    }

    public function rotate_photo()
    {
        $scope = trim((string) $this->input->post('scope', TRUE));
        $property = trim((string) $this->input->post('property', TRUE));
        $type = trim((string) $this->input->post('type', TRUE));
        $file = basename(rawurldecode((string) $this->input->post('file', TRUE)));
        $degrees = (int) $this->input->post('degrees');
        $directory = $scope === 'slider'
            ? $this->uploads_path('SLIDER')
            : $this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type));
        $path = $this->safe_upload_file($directory, $file);
        $ok = $path && is_file($path) && (! empty($_FILES['rotated_file'])
            ? $this->store_rotated_photo($path, $degrees) : $this->rotate_image_file($path, $degrees));

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'ok' => $ok,
                'message' => $ok ? 'Rotasi foto tersimpan.' : 'Rotasi foto belum bisa disimpan.',
                'cache_buster' => time(),
            )));
    }

    public function tenants()
    {
        $filters = array(
            'property_id' => (int) $this->input->get('property_id'),
            'room_type_id' => (int) $this->input->get('room_type_id'),
            'room_id' => (int) $this->input->get('room_id'),
            'stay_status' => trim((string) $this->input->get('stay_status', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
        );

        $this->load->view('manage/tenants', array(
            'title' => 'Penghuni | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_tenant_stays($filters),
            'rooms' => $this->Master_model->get_active_rooms_for_stay(),
            'properties' => $this->Master_model->get_active_properties(),
            'room_types' => $this->Master_model->get_active_room_types(),
            'filters' => $filters,
        ));
    }

    public function save_tenant()
    {
        $birthPlace = trim((string) $this->input->post('birth_place', TRUE));
        $birthDate = $this->nullable_date($this->input->post('birth_date'));
        $birthDateText = trim((string) $this->input->post('birth_date_text', TRUE));
        $birthPlaceDate = trim((string) $this->input->post('birth_place_date', TRUE));
        if ($birthPlace !== '' || $birthDate !== null || $birthDateText !== '') {
            $birthValue = $birthDate !== null ? $birthDate : $birthDateText;
            $birthPlaceDate = trim($birthPlace . ($birthPlace !== '' && $birthValue !== '' ? ', ' : '') . $birthValue);
        }

        $payload = array(
            'stay_id' => (int) $this->input->post('stay_id'),
            'tenant_id' => (int) $this->input->post('tenant_id'),
            'room_id' => (int) $this->input->post('room_id'),
            'stay_status' => trim((string) $this->input->post('stay_status', TRUE)),
            'fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'birth_place_date' => $birthPlaceDate,
            'religion' => trim((string) $this->input->post('religion', TRUE)),
            'identity_number' => preg_replace('/[^0-9]/', '', (string) $this->input->post('identity_number', TRUE)),
            'phone' => trim((string) $this->input->post('phone', TRUE)),
            'marital_status' => trim((string) $this->input->post('marital_status', TRUE)),
            'occupation' => trim((string) $this->input->post('occupation', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)),
            'emergency_name' => trim((string) $this->input->post('emergency_name', TRUE)),
            'emergency_relationship' => trim((string) $this->input->post('emergency_relationship', TRUE)),
            'emergency_phone' => trim((string) $this->input->post('emergency_phone', TRUE)),
            'check_in_date' => $this->nullable_date($this->input->post('check_in_date')),
            'check_out_date' => $this->nullable_date($this->input->post('check_out_date')),
            'monthly_price' => $this->nullable_money($this->input->post('monthly_price')),
            'deposit' => $this->nullable_money($this->input->post('deposit')),
            'source_period' => trim((string) $this->input->post('source_period', TRUE)),
            'source_unit_name' => trim((string) $this->input->post('source_unit_name', TRUE)),
            'notes' => trim((string) $this->input->post('notes', TRUE)),
        );

        $ok = $this->Master_model->save_tenant_stay($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Data penghuni tersimpan.' : 'Data penghuni belum tersimpan. Cek tanggal masuk/keluar, bentrok kamar, NIK yang sudah terdaftar, dan batas perubahan stay berkontrak aktif.');
        gh_mobile_redirect('tenants', 'location', 303);
    }

    public function end_tenant_stay($id)
    {
        $ok = $this->Master_model->end_tenant_stay((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Stay penghuni diakhiri dan kamar dibuka kembali.' : 'Stay belum bisa diakhiri. Gunakan Proses Kontrak untuk checkout atau pembatalan reservasi yang memiliki kontrak.');
        gh_mobile_redirect('tenants', 'location', 303);
    }

    public function payments()
    {
        $filters = array(
            'period_id' => (int) $this->input->get('period_id'),
            'property_id' => (int) $this->input->get('property_id'),
            'room_type_id' => (int) $this->input->get('room_type_id'),
            'room_id' => (int) $this->input->get('room_id'),
            'status' => trim((string) $this->input->get('status', TRUE)),
            'has_payment' => trim((string) $this->input->get('has_payment', TRUE)),
            'q' => trim((string) $this->input->get('q', TRUE)),
        );

        $rows=$this->Master_model->get_payment_bills($filters);
        $billOptions=$this->Master_model->get_payment_bill_options();$quoteIds=array();foreach(array_merge($rows,$billOptions) as $bill)$quoteIds[]=(int)$bill['id'];
        $this->load->view('manage/payments', array(
            'title' => 'Pembayaran | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'periods' => $this->Master_model->get_billing_periods(),
            'properties' => $this->Master_model->get_active_properties(),
            'room_types' => $this->Master_model->get_active_room_types(),
            'rooms' => $this->Master_model->get_rooms_for_payment_filters(),
            'filters' => $filters,
            'summary' => $this->Master_model->get_payment_summary($filters),
            'rows' => $rows,
            'issues' => $this->Master_model->get_payment_import_issues(),
            'bill_options' => $billOptions,
            'payment_quotes' => $this->Master_model->get_payment_quotes($quoteIds),
        ));
    }

    public function save_payment_bill()
    {
        $this->session->set_flashdata('error', 'Tagihan bersifat baca-saja dan diterbitkan otomatis dari kesepakatan sewa.');
        gh_mobile_redirect($this->payment_redirect_url(), 'location', 303);
    }

    public function save_payment()
    {
        $payload = array(
            'payment_id' => (int) $this->input->post('payment_id'),
            'request_key' => trim((string) $this->input->post('request_key', TRUE)),
            'room_bill_id' => (int) $this->input->post('room_bill_id'),
            'payment_date' => $this->nullable_date($this->input->post('payment_date')),
            'payment_date_text' => trim((string) $this->input->post('payment_date_text', TRUE)),
            'amount' => $this->nullable_money($this->input->post('amount')),
            'payment_type' => trim((string) $this->input->post('payment_type', TRUE)),
            'method' => trim((string) $this->input->post('method', TRUE)),
            'reference_no' => trim((string) $this->input->post('reference_no', TRUE)),
            'notes' => trim((string) $this->input->post('notes', TRUE)),
        );

        if($payload['payment_id']>0){$posted=$this->input->post(NULL,FALSE)?:array();foreach(array('amount','payment_type','method','payment_date','payment_date_text','reference_no') as $field)if(!array_key_exists($field,$posted))unset($payload[$field]);}
        $ok = $this->Master_model->save_payment($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pembayaran tersimpan.' : ($this->Master_model->get_payment_error()?:'Pembayaran belum tersimpan. Muat ulang sisa tagihan dan gunakan nominal otomatis dengan metode Transfer.'));
        gh_mobile_redirect($this->payment_redirect_url(), 'location', 303);
    }

    public function delete_payment($id)
    {
        $ok = $this->Master_model->delete_payment((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pembayaran dihapus.' : 'Pembayaran belum bisa dihapus.');
        gh_mobile_redirect($this->payment_redirect_url(), 'location', 303);
    }

    private function nullable_int($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function nullable_money($value)
    {
        if (! is_scalar($value) && $value !== null) return -1;
        $value = trim((string) $value);
        if ($value === '') return null;
        if (! preg_match('/^(?:Rp\s*)?(?:\d+|\d{1,3}(?:\.\d{3})+)(?:,00)?$/i', $value)) return -1;
        $digits = str_replace('.', '', preg_replace('/,00$/', '', preg_replace('/^Rp\s*/i', '', $value)));
        return strlen($digits) > 15 ? -1 : (int) $digits;
    }

    private function nullable_date($value)
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function payment_redirect_url()
    {
        $params = array();
        foreach (array('period_id', 'property_id', 'room_type_id', 'room_id', 'status', 'has_payment', 'q') as $key) {
            $value = trim((string) $this->input->post_get($key, TRUE));
            if ($value !== '') {
                $params[$key] = $value;
            }
        }

        return 'payments' . ($params ? '?' . http_build_query($params) : '');
    }

    private function list_slider_photos()
    {
        return $this->list_photos_in_directory($this->uploads_path('SLIDER'), 'slider', '', '');
    }

    private function list_room_photos($property, $type)
    {
        if ($property === '' || $type === '') {
            return array();
        }

        return $this->list_photos_in_directory($this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type)), 'room', $property, $type);
    }

    private function list_room_photo_groups()
    {
        $root = $this->uploads_path('');
        $groups = array();

        if (! is_dir($root)) {
            return $groups;
        }

        foreach (new DirectoryIterator($root) as $propertyDir) {
            if (! $propertyDir->isDir() || $propertyDir->isDot() || strtoupper($propertyDir->getFilename()) === 'SLIDER') {
                continue;
            }

            foreach (new DirectoryIterator($propertyDir->getPathname()) as $typeDir) {
                if (! $typeDir->isDir() || $typeDir->isDot()) {
                    continue;
                }

                $photos = $this->list_photos_in_directory($typeDir->getPathname(), 'room', $propertyDir->getFilename(), $typeDir->getFilename());
                if ($photos) {
                    $groups[] = array(
                        'property' => $propertyDir->getFilename(),
                        'type' => $typeDir->getFilename(),
                        'count' => count($photos),
                    );
                }
            }
        }

        return $groups;
    }

    private function list_photos_in_directory($directory, $scope, $property, $type)
    {
        $photos = array();

        if (! is_dir($directory)) {
            return $photos;
        }

        $cover = $scope === 'room' ? Gulhouse_gallery_cover::selected($directory) : '';
        foreach (new DirectoryIterator($directory) as $file) {
            if (! $file->isFile() || ! $this->is_allowed_image($file->getFilename())) {
                continue;
            }

            $filename = $file->getFilename();
            $focus = $scope === 'room' ? Gulhouse_gallery_focus::get($directory,$filename) : array('x'=>50,'y'=>70);
            $photos[] = array(
                'filename' => $filename,
                'focus_x' => $focus['x'],
                'focus_y' => $focus['y'],
                'is_cover' => $scope === 'room' && $filename === $cover,
                'size' => $file->getSize(),
                'updated_at' => date('d M Y H:i', $file->getMTime()),
                'category' => $scope === 'slider' ? 'Slider' : $this->category_from_filename($filename),
                'url' => base_url('photos/view?scope=' . rawurlencode($scope) . '&property=' . rawurlencode($property) . '&type=' . rawurlencode($type) . '&file=' . rawurlencode($filename)),
                'delete_url' => base_url('photos/delete?scope=' . rawurlencode($scope) . '&property=' . rawurlencode($property) . '&type=' . rawurlencode($type) . '&file=' . rawurlencode($filename)),
            );
        }

        usort($photos, function ($left, $right) {
            return strnatcasecmp($left['filename'], $right['filename']);
        });

        return $photos;
    }

    private function store_uploaded_photos($field, $directory, $category)
    {
        if (empty($_FILES[$field]['name'][0])) {
            return array('ok' => FALSE, 'message' => 'Pilih minimal satu foto.');
        }

        if (! is_dir($directory) && ! mkdir($directory, 0775, TRUE)) {
            return array('ok' => FALSE, 'message' => 'Folder upload belum bisa dibuat.');
        }

        $saved = 0;
        $failed = 0;
        $files = $_FILES[$field];

        foreach ($files['name'] as $index => $originalName) {
            if ((int) $files['error'][$index] !== UPLOAD_ERR_OK) {
                $failed++;
                continue;
            }

            $imageInfo = @getimagesize($files['tmp_name'][$index]);
            $expectedTypes = array('jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG, 'webp' => IMAGETYPE_WEBP);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (! $this->is_allowed_image($originalName) || ! $imageInfo || ! isset($expectedTypes[$extension])
                || $imageInfo[2] !== $expectedTypes[$extension] || (int) $files['size'][$index] > 10 * 1024 * 1024
                || $imageInfo[0] * $imageInfo[1] > 40000000) {
                $failed++;
                continue;
            }

            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $prefix = $this->filename_prefix($category);
            $filename = $prefix . '_' . date('YmdHis') . '_' . sprintf('%03d', $index + 1) . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
            $target = $directory . DIRECTORY_SEPARATOR . $filename;

            if (move_uploaded_file($files['tmp_name'][$index], $target)) {
                @chmod($target, 0664);
                $saved++;
            } else {
                $failed++;
            }
        }

        if ($saved === 0) {
            return array('ok' => FALSE, 'saved' => 0, 'failed' => $failed, 'message' => 'Foto belum tersimpan. Pastikan format JPG, JPEG, PNG, atau WEBP.');
        }

        $message = $saved . ' foto berhasil disimpan.';
        if ($failed > 0) {
            $message .= ' ' . $failed . ' file dilewati.';
        }

        return array('ok' => TRUE, 'saved' => $saved, 'failed' => $failed, 'message' => $message);
    }

    private function uploads_path($relative)
    {
        $root = realpath(FCPATH . '../uploads');
        if (! $root) {
            $root = FCPATH . '../uploads';
        }

        $relative = trim(str_replace('\\', '/', (string) $relative), '/');
        return $relative === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private function safe_upload_file($directory, $filename)
    {
        $base = realpath($directory);
        $path = $base ? realpath($base . DIRECTORY_SEPARATOR . basename((string) $filename)) : FALSE;

        if (! $base || ! $path || strpos($path, $base) !== 0) {
            return '';
        }

        return $path;
    }

    private function photo_categories()
    {
        return array(
            'kamar' => 'Kamar',
            'mandi' => 'Kamar Mandi',
            'fasilitas' => 'Fasilitas',
            'umum' => 'Area Umum',
        );
    }

    private function resolve_photo_category($value, array $categories)
    {
        $value = trim((string) $value);
        if ($value === '' || strtolower($value) === 'all') {
            return 'all';
        }

        if (isset($categories[$value])) {
            return $categories[$value];
        }

        foreach ($categories as $label) {
            if (strcasecmp($value, $label) === 0) {
                return $label;
            }
        }

        return 'all';
    }

    private function filename_prefix($category)
    {
        $prefixes = array(
            'slider' => 'image',
            'kamar' => 'kamar',
            'mandi' => 'MANDI',
            'fasilitas' => 'FASILITAS',
            'umum' => 'UMUM',
        );

        return isset($prefixes[$category]) ? $prefixes[$category] : 'foto';
    }

    private function category_from_filename($filename)
    {
        $name = strtoupper((string) $filename);

        if (strpos($name, 'MANDI') === 0) {
            return 'Kamar Mandi';
        }

        if (strpos($name, 'FASILITAS') === 0) {
            return 'Fasilitas';
        }

        if (strpos($name, 'UMUM') === 0 || strpos($name, 'AREA') === 0) {
            return 'Area Umum';
        }

        return 'Kamar';
    }

    private function store_rotated_photo($path, $degrees)
    {
        if (! in_array($degrees, array(90, 180, 270), TRUE)) { return FALSE; }
        $file = $_FILES['rotated_file'];
        if ($file['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name']) || $file['size'] > 10 * 1024 * 1024) { return FALSE; }
        $original = @getimagesize($path);
        $rotated = @getimagesize($file['tmp_name']);
        if (! $original || ! $rotated || $original[2] !== $rotated[2]) { return FALSE; }
        $swap = $degrees === 90 || $degrees === 270;
        if ($rotated[0] !== $original[$swap ? 1 : 0] || $rotated[1] !== $original[$swap ? 0 : 1]) { return FALSE; }
        return move_uploaded_file($file['tmp_name'], $path);
    }

    private function rotate_image_file($path, $degrees)
    {
        $degrees = $degrees % 360;
        if ($degrees < 0) {
            $degrees += 360;
        }

        if ($degrees === 0 || ! function_exists('imagerotate')) {
            return $degrees === 0;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'jpg' || $extension === 'jpeg') {
            $image = @imagecreatefromjpeg($path);
        } elseif ($extension === 'png') {
            $image = @imagecreatefrompng($path);
        } elseif ($extension === 'webp' && function_exists('imagecreatefromwebp')) {
            $image = @imagecreatefromwebp($path);
        } else {
            return FALSE;
        }

        if (! $image) {
            return FALSE;
        }

        $rotated = imagerotate($image, -$degrees, 0);
        imagedestroy($image);

        if (! $rotated) {
            return FALSE;
        }

        if ($extension === 'png' || $extension === 'webp') {
            imagealphablending($rotated, FALSE);
            imagesavealpha($rotated, TRUE);
        }

        if ($extension === 'jpg' || $extension === 'jpeg') {
            $ok = imagejpeg($rotated, $path, 90);
        } elseif ($extension === 'png') {
            $ok = imagepng($rotated, $path, 6);
        } else {
            $ok = function_exists('imagewebp') ? imagewebp($rotated, $path, 88) : FALSE;
        }

        imagedestroy($rotated);
        return $ok;
    }

    private function folder_key($value)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));
    }

    private function resolve_photo_value($value, array $rows, $field)
    {
        if ($value === '') {
            return '';
        }

        $target = $this->folder_key($value);
        foreach ($rows as $row) {
            if (isset($row[$field]) && $this->folder_key($row[$field]) === $target) {
                return $row[$field];
            }
        }

        return $value;
    }

    private function is_allowed_image($filename)
    {
        return (bool) preg_match('/\.(jpe?g|png|webp)$/i', (string) $filename);
    }

    private function master_error($route, $message)
    {
        $this->session->set_flashdata('error', $message);
        gh_mobile_redirect($route, 'location', 303);
    }

    private function has_enabled_rooms($field, $id)
    {
        return $id > 0 && $this->db->where($field, $id)->where('status !=', 'inactive')->count_all_results('gh_rooms') > 0;
    }

    private function has_open_stay($roomId)
    {
        return $roomId > 0 && $this->db->table_exists('gh_tenant_stays') && $this->db->where('room_id', $roomId)
            ->where_in('stay_status', array('active', 'reserved', 'internal'))->count_all_results('gh_tenant_stays') > 0;
    }

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            $this->session->set_flashdata('error', 'Sesi belum aktif. Silakan login terlebih dahulu.');
            gh_mobile_redirect('login', 'location', 303);
        }
    }
}

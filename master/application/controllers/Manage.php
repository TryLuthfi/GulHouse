<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
        $this->load->view('manage/properties', array(
            'title' => 'Properti | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_properties(),
            'edit' => $id > 0 ? $this->Master_model->get_property($id) : null,
        ));
    }

    public function save_property()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'code' => strtoupper(trim((string) $this->input->post('code', TRUE))),
            'name' => trim((string) $this->input->post('name', TRUE)),
            'property_type' => trim((string) $this->input->post('property_type', TRUE)),
            'address' => trim((string) $this->input->post('address', TRUE)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        );

        $ok = $this->Master_model->save_property($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti tersimpan.' : 'Properti belum tersimpan. Cek kode dan nama.');
        redirect('properties');
    }

    public function delete_property($id)
    {
        $ok = $this->Master_model->soft_delete_property((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Properti dinonaktifkan.' : 'Properti belum bisa dinonaktifkan.');
        redirect('properties');
    }

    public function room_types()
    {
        $id = (int) $this->input->get('id');
        $this->load->view('manage/room_types', array(
            'title' => 'Tipe Kamar | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_room_types(),
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

        $ok = $this->Master_model->save_room_type($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar tersimpan.' : 'Tipe kamar belum tersimpan.');
        redirect('room-types');
    }

    public function delete_room_type($id)
    {
        $ok = $this->Master_model->soft_delete_room_type((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Tipe kamar dinonaktifkan.' : 'Tipe kamar belum bisa dinonaktifkan.');
        redirect('room-types');
    }

    public function rooms()
    {
        $id = (int) $this->input->get('id');
        $this->load->view('manage/rooms', array(
            'title' => 'Kamar | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_rooms(),
            'properties' => $this->Master_model->get_active_properties(),
            'room_types' => $this->Master_model->get_active_room_types(),
            'edit' => $id > 0 ? $this->Master_model->get_room($id) : null,
        ));
    }

    public function save_room()
    {
        $payload = array(
            'id' => (int) $this->input->post('id'),
            'property_id' => (int) $this->input->post('property_id'),
            'room_type_id' => (int) $this->input->post('room_type_id'),
            'room_code' => strtoupper(trim((string) $this->input->post('room_code', TRUE))),
            'room_name' => trim((string) $this->input->post('room_name', TRUE)),
            'room_label' => trim((string) $this->input->post('room_label', TRUE)),
            'floor_no' => $this->nullable_int($this->input->post('floor_no')),
            'deposit' => $this->nullable_money($this->input->post('deposit')),
            'monthly_price' => $this->nullable_money($this->input->post('monthly_price')),
            'status' => trim((string) $this->input->post('status', TRUE)),
            'is_public' => $this->input->post('is_public') ? 1 : 0,
        );

        $ok = $this->Master_model->save_room($payload);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar tersimpan.' : 'Kamar belum tersimpan.');
        redirect('rooms');
    }

    public function delete_room($id)
    {
        $ok = $this->Master_model->soft_delete_room((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Kamar diarsipkan.' : 'Kamar belum bisa diarsipkan.');
        redirect('rooms');
    }

    public function toggle_room_public($id)
    {
        $this->Master_model->toggle_room_public((int) $id);
        redirect('rooms');
    }

    public function photos()
    {
        $selectedProperty = strtoupper(trim((string) $this->input->get('property', TRUE)));
        $selectedType = trim((string) $this->input->get('type', TRUE));
        $properties = $this->Master_model->get_active_properties();
        $roomTypes = $this->Master_model->get_active_room_types();
        $selectedProperty = $this->resolve_photo_value($selectedProperty, $properties, 'code');
        $selectedType = $this->resolve_photo_value($selectedType, $roomTypes, 'name');

        $this->load->view('manage/photos', array(
            'title' => 'Foto | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'properties' => $properties,
            'room_types' => $roomTypes,
            'selected_property' => $selectedProperty,
            'selected_type' => $selectedType,
            'slider_photos' => $this->list_slider_photos(),
            'room_photos' => $this->list_room_photos($selectedProperty, $selectedType),
            'room_photo_groups' => $this->list_room_photo_groups(),
            'categories' => $this->photo_categories(),
        ));
    }

    public function upload_slider_photos()
    {
        $result = $this->store_uploaded_photos('photos', $this->uploads_path('SLIDER'), 'slider');
        $this->session->set_flashdata($result['ok'] ? 'success' : 'error', $result['message']);
        redirect('photos');
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
            redirect('photos');
            return;
        }

        $message = $savedTotal . ' foto berhasil disimpan dari batch upload.';
        if ($failedTotal > 0) {
            $message .= ' ' . $failedTotal . ' row/file dilewati.';
        }

        $this->session->set_flashdata('success', $message);
        redirect($lastProperty && $lastType ? 'photos?property=' . rawurlencode($lastProperty) . '&type=' . rawurlencode($lastType) : 'photos');
    }

    public function delete_photo()
    {
        $scope = trim((string) $this->input->get('scope', TRUE));
        $property = trim((string) $this->input->get('property', TRUE));
        $type = trim((string) $this->input->get('type', TRUE));
        $file = basename(rawurldecode((string) $this->input->get('file', TRUE)));
        $directory = $scope === 'slider'
            ? $this->uploads_path('SLIDER')
            : $this->uploads_path($this->folder_key($property) . '/' . $this->folder_key($type));
        $path = $this->safe_upload_file($directory, $file);

        if (! $path || ! is_file($path)) {
            $this->session->set_flashdata('error', 'Foto tidak ditemukan.');
            redirect('photos');
            return;
        }

        $ok = unlink($path);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Foto berhasil dihapus.' : 'Foto belum bisa dihapus.');
        redirect($scope === 'slider' ? 'photos' : 'photos?property=' . rawurlencode($property) . '&type=' . rawurlencode($type));
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
        $ok = $path && is_file($path) && $this->rotate_image_file($path, $degrees);

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
        $this->load->view('manage/tenants', array(
            'title' => 'Penghuni | GUL HOUSE',
            'admin_name' => $this->session->userdata('gh_admin_name'),
            'rows' => $this->Master_model->get_tenant_stays(),
            'rooms' => $this->Master_model->get_active_rooms_for_stay(),
        ));
    }

    public function save_tenant()
    {
        $payload = array(
            'stay_id' => (int) $this->input->post('stay_id'),
            'tenant_id' => (int) $this->input->post('tenant_id'),
            'room_id' => (int) $this->input->post('room_id'),
            'stay_status' => trim((string) $this->input->post('stay_status', TRUE)),
            'fullname' => trim((string) $this->input->post('fullname', TRUE)),
            'birth_place_date' => trim((string) $this->input->post('birth_place_date', TRUE)),
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
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Data penghuni tersimpan.' : 'Data penghuni belum tersimpan.');
        redirect('tenants');
    }

    public function end_tenant_stay($id)
    {
        $ok = $this->Master_model->end_tenant_stay((int) $id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Stay penghuni diakhiri dan kamar dibuka kembali.' : 'Stay belum bisa diakhiri.');
        redirect('tenants');
    }

    private function nullable_int($value)
    {
        $value = trim((string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function nullable_money($value)
    {
        $value = preg_replace('/[^0-9]/', '', (string) $value);
        return $value === '' ? null : (int) $value;
    }

    private function nullable_date($value)
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
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

        foreach (new DirectoryIterator($directory) as $file) {
            if (! $file->isFile() || ! $this->is_allowed_image($file->getFilename())) {
                continue;
            }

            $filename = $file->getFilename();
            $photos[] = array(
                'filename' => $filename,
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

            if (! $this->is_allowed_image($originalName) || ! @getimagesize($files['tmp_name'][$index])) {
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

    private function require_login()
    {
        if ( ! $this->session->userdata('gh_admin_logged_in')) {
            redirect('login');
        }
    }
}

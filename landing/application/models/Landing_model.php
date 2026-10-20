<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing_model extends CI_Model
{
    private $booking_error = '';
    private $booking_error_field = '';

    public function get_booking_error() { return $this->booking_error; }
    public function get_booking_error_field() { return $this->booking_error_field; }
    private function booking_rejected($field,$message)
    {
        $this->booking_error_field=$field;$this->booking_error=$message;return FALSE;
    }
    public function get_summary()
    {
        $fallback = array(
            'total_rooms' => 66,
            'available_rooms' => 11,
            'occupied_rooms' => 55,
            'occupancy_rate' => 83,
            'starting_price' => 1500000,
        );

        if ($this->table_exists('gh_rooms')) {
            $row = $this->db
                ->select('COUNT(id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND status = "available" AND is_public = 1 THEN 1 ELSE 0 END) AS available_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND status NOT IN ("inactive", "mess") THEN monthly_price END) AS starting_price', FALSE)
                ->get('gh_rooms')
                ->row_array();

            $total = (int) $row['total_rooms'];
            $available = (int) $row['available_rooms'];
            $occupied = max(0, $total - $available);

            return array(
                'total_rooms' => $total,
                'available_rooms' => $available,
                'occupied_rooms' => $occupied,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
                'starting_price' => isset($row['starting_price']) ? (int) $row['starting_price'] : $fallback['starting_price'],
            );
        }

        if ($this->table_exists('gh_room_database')) {
            $row = $this->db
                ->select('COUNT(id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS starting_price', FALSE)
                ->get('gh_room_database')
                ->row_array();

            $total = (int) $row['total_rooms'];
            $available = (int) $row['available_rooms'];
            $occupied = max(0, $total - $available);

            return array(
                'total_rooms' => $total,
                'available_rooms' => $available,
                'occupied_rooms' => $occupied,
                'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
                'starting_price' => isset($row['starting_price']) ? (int) $row['starting_price'] : $fallback['starting_price'],
            );
        }

        if ( ! $this->table_exists('rooms')) {
            return $fallback;
        }

        $total = (int) $this->db->count_all('rooms');
        $available = (int) $this->db->where('status', 'available')->count_all_results('rooms');
        $occupied = (int) $this->db->where('status', 'occupied')->count_all_results('rooms');
        $price = $this->db->select_min('price', 'starting_price')->where('price >', 0)->get('rooms')->row_array();

        return array(
            'total_rooms' => $total,
            'available_rooms' => $available,
            'occupied_rooms' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0,
            'starting_price' => isset($price['starting_price']) ? (int) $price['starting_price'] : $fallback['starting_price'],
        );
    }

    public function get_room_types()
    {
        if ($this->table_exists('gh_rooms')) {
            $rows = $this->db
                ->select('CONCAT(p.code, " - ", rt.name) AS name', FALSE)
                ->select('p.code AS property_code', FALSE)
                ->select('rt.name AS room_type', FALSE)
                ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN 1 ELSE 0 END) AS total_rooms', FALSE)
                ->select('MIN(CASE WHEN r.monthly_price IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN r.monthly_price END) AS price_from', FALSE)
                ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status = "available" AND r.is_public = 1 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_rooms r')
                ->join('gh_properties p', 'p.id = r.property_id', 'left')
                ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
                ->group_by('p.id, rt.id')
                ->having('total_rooms >', 0)
                ->order_by('p.code', 'ASC')
                ->order_by('FIELD(rt.name, "Standart", "Deluxe", "VIP")', '', FALSE)
                ->order_by('rt.name', 'ASC')
                ->get()
                ->result_array();

            return $rows ? $rows : $this->fallback_room_types();
        }

        if ($this->table_exists('gh_room_database')) {
            $rows = $this->db
                ->select('CONCAT(property_code, " - ", room_type) AS name', FALSE)
                ->select('property_code', FALSE)
                ->select('room_type', FALSE)
                ->select('COUNT(id) AS total_rooms', FALSE)
                ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS price_from', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_room_database')
                ->group_by('property_code, room_type')
                ->order_by('property_code', 'ASC')
                ->order_by('FIELD(room_type, "Standart", "Deluxe", "VIP")', '', FALSE)
                ->order_by('room_type', 'ASC')
                ->get()
                ->result_array();

            return $rows ? $rows : $this->fallback_room_types();
        }

        if ( ! $this->table_exists('rooms')) {
            return $this->fallback_room_types();
        }

        $this->db->select('COALESCE(rt.name, "Room") AS name, COUNT(r.id) AS total_rooms, MIN(r.price) AS price_from, SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE);
        $this->db->from('rooms r');
        $this->db->join('room_types rt', 'rt.id = r.room_type_id', 'left');
        $this->db->group_by('r.room_type_id, rt.name');
        $this->db->order_by('price_from', 'ASC');
        $rows = $this->db->get()->result_array();

        return $rows ? $rows : $this->fallback_room_types();
    }

    public function get_featured_rooms()
    {
        if ($this->table_exists('gh_rooms')) {
            return $this->get_public_master_room_type_cards();
        }

        if ($this->table_exists('gh_room_database')) {
            return $this->get_public_room_type_cards();
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('COALESCE(rt.name, "Room") AS type_name', FALSE)
                ->select('COUNT(r.id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->select('MIN(CASE WHEN r.price > 0 THEN r.price END) AS price', FALSE)
                ->select('MAX(CASE WHEN r.price > 0 THEN r.price END) AS max_price', FALSE)
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->group_by('r.room_type_id, rt.name')
                ->order_by('price', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_public_type_rows($rows);
            }
        }

        return $this->fallback_public_room_type_cards();
    }

    public function get_all_public_rooms()
    {
        if ($this->table_exists('gh_rooms')) {
            return $this->get_public_master_room_type_cards();
        }

        if ($this->table_exists('gh_room_database')) {
            return $this->get_public_room_type_cards();
        }

        if ($this->table_exists('rooms')) {
            $rows = $this->db
                ->select('COALESCE(rt.name, "Room") AS type_name', FALSE)
                ->select('COUNT(r.id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->select('MIN(CASE WHEN r.price > 0 THEN r.price END) AS price', FALSE)
                ->select('MAX(CASE WHEN r.price > 0 THEN r.price END) AS max_price', FALSE)
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->group_by('r.room_type_id, rt.name')
                ->order_by('price', 'ASC')
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_public_type_rows($rows);
            }
        }

        return $this->fallback_public_room_type_cards();
    }

    public function get_amenities()
    {
        return array(
            array('name' => 'Kamar Furnished', 'description' => 'Kasur, lemari, meja, kursi, dan perlengkapan dasar siap pakai.'),
            array('name' => 'Kontrol Fasilitas', 'description' => 'AC, listrik, air, kamar mandi, dan area umum dicek berkala.'),
            array('name' => 'Akses 24 Jam', 'description' => 'Penghuni tetap leluasa masuk-keluar sesuai tata tertib.'),
            array('name' => 'Area Umum', 'description' => 'Koridor, lobby, parkir, dan fasilitas bersama dikelola terjadwal.'),
            array('name' => 'Maintenance Tercatat', 'description' => 'Keluhan dan pekerjaan teknis dilacak sampai selesai.'),
            array('name' => 'Booking Mudah', 'description' => 'Calon penghuni bisa inquiry dan jadwalkan survey dari website.'),
        );
    }

    public function get_testimonials()
    {
        return array(
            array('name' => 'Ayu', 'room' => 'GH 1', 'quote' => 'Kamar rapi dan proses masuknya jelas. Cocok untuk yang butuh tempat tinggal praktis.'),
            array('name' => 'Rizky', 'room' => 'GH 2', 'quote' => 'Lokasi mudah dijangkau, pengelola responsif saat ada kendala fasilitas.'),
            array('name' => 'Diva', 'room' => 'Cappadocia', 'quote' => 'Yang saya suka, biaya dan aturan tinggal dijelaskan sejak awal.'),
        );
    }

    public function get_cost_items()
    {
        return array(
            array('label' => 'Sewa bulanan', 'value' => 'Mulai Rp 1.500.000'),
            array('label' => 'Deposit', 'value' => 'Rp. 500.000'),
            array('label' => 'Survey', 'value' => 'By appointment'),
        );
    }

    public function get_house_rules_document()
    {
        if (! $this->table_exists('gh_document_templates')) return NULL;
        $template = $this->db->where('template_key', 'house_rules')->where('is_active', 1)
            ->get('gh_document_templates')->row_array();
        if (! $template) return NULL;
        // Only this public template is exposed; use the same attachment as master.
        $template['pdf_path'] = dirname(rtrim(FCPATH, '/\\')) . '/master/application/private_documents/template_'
            . substr(hash('sha256', $this->db->database), 0, 16) . '_' . (int) $template['id'] . '.pdf';
        return $template;
    }

    public function get_house_rules()
    {
        return array(
            'Data penghuni wajib lengkap sebelum check-in.',
            'Pembayaran mengikuti tanggal jatuh tempo yang disepakati.',
            'Tamu dan parkir mengikuti aturan masing-masing properti.',
            'Kerusakan fasilitas wajib dilaporkan ke pengelola.',
        );
    }

    public function get_nearby_places()
    {
        return array(
            array('place' => 'Akses transport', 'distance' => 'dekat area utama'),
            array('place' => 'Minimarket', 'distance' => 'jalan kaki'),
            array('place' => 'Kuliner sekitar', 'distance' => 'banyak pilihan'),
            array('place' => 'Area parkir', 'distance' => 'tersedia terbatas'),
        );
    }

    public function get_property_stats()
    {
        if ($this->table_exists('gh_rooms')) {
            return $this->db
                ->select('p.code AS property_name, COUNT(r.id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status = "available" AND r.is_public = 1 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_properties p')
                ->join('gh_rooms r', 'r.property_id = p.id', 'left')
                ->group_by('p.id')
                ->order_by('p.code', 'ASC')
                ->get()
                ->result_array();
        }

        if ($this->table_exists('gh_room_database')) {
            return $this->db
                ->select('property_code AS property_name, COUNT(id) AS total_rooms', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
                ->from('gh_room_database')
                ->group_by('property_code')
                ->order_by('property_code', 'ASC')
                ->get()
                ->result_array();
        }

        if ( ! $this->table_exists('rooms')) {
            return array(
                array('property_name' => 'GH 1', 'total_rooms' => 29, 'available_count' => 7),
                array('property_name' => 'GH 2', 'total_rooms' => 34, 'available_count' => 4),
                array('property_name' => 'Apartemen', 'total_rooms' => 3, 'available_count' => 1),
            );
        }

        return $this->db
            ->select('code AS property_name, COUNT(id) AS total_rooms, SUM(CASE WHEN status = "available" THEN 1 ELSE 0 END) AS available_count', FALSE)
            ->from('rooms')
            ->group_by('code')
            ->order_by('code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_room_type_detail($slug)
    {
        $slug = $this->normalize_slug($slug);
        if ($slug === '') {
            return FALSE;
        }

        $cards = $this->table_exists('gh_rooms') ? $this->get_public_master_room_type_cards() : $this->get_public_room_type_cards();
        foreach ($cards as $card) {
            if ($card['slug'] === $slug) {
                return $this->normalize_public_room_type_detail($card);
            }
        }

        return FALSE;
    }

    public function get_room_detail($id)
    {
        if ($id <= 0) {
            return $this->fallback_room_detail(1);
        }

        if ($this->table_exists('gh_room_database')) {
            $room = $this->db
                ->select('id, property_code, room_code, room_type, room_name, room_label, deposit, monthly_price, is_mess, floor_no')
                ->from('gh_room_database')
                ->where('id', $id)
                ->get()
                ->row_array();

            if ($room) {
                return $this->normalize_gh_room_detail($room);
            }
        }

        if ($this->table_exists('rooms')) {
            $room = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, r.notes, rt.name AS type_name, rt.description AS type_description')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->where('r.id', $id)
                ->get()
                ->row_array();

            if ($room) {
                $room['facilities'] = $this->get_room_assets((int) $room['id']);
                return $this->normalize_room_detail($room);
            }
        }

        return $this->fallback_room_detail($id);
    }

    public function get_similar_rooms(array $room)
    {
        if ($this->table_exists('gh_room_database') && isset($room['id'])) {
            $rows = $this->db
                ->select('id, property_code, room_code, room_type, room_label, monthly_price, is_mess')
                ->from('gh_room_database')
                ->where('id !=', (int) $room['id'])
                ->where('monthly_price IS NOT NULL', NULL, FALSE)
                ->where('is_mess', 0)
                ->order_by('monthly_price', 'ASC')
                ->order_by('property_code', 'ASC')
                ->limit(3)
                ->get()
                ->result_array();

            if ($rows) {
                return $this->map_gh_rooms($rows);
            }
        }

        if ($this->table_exists('rooms') && isset($room['id'])) {
            $rows = $this->db
                ->select('r.id, r.code, r.name, r.price, r.status, rt.name AS type_name')
                ->from('rooms r')
                ->join('room_types rt', 'rt.id = r.room_type_id', 'left')
                ->where('r.id !=', (int) $room['id'])
                ->where('r.status', 'available')
                ->order_by('r.price', 'ASC')
                ->limit(3)
                ->get()
                ->result_array();

            if ($rows) {
                return $rows;
            }
        }

        return array(
            array('id' => 1, 'code' => 'GH 1', 'name' => 'GH 1 - VIP', 'type_name' => 'VIP', 'price' => 2000000, 'status' => 'available'),
            array('id' => 2, 'code' => 'GH 2', 'name' => 'GH 2 - Standart', 'type_name' => 'Standart', 'price' => 2000000, 'status' => 'available'),
            array('id' => 3, 'code' => 'GH 2', 'name' => 'GH 2 - VIP', 'type_name' => 'VIP', 'price' => 3000000, 'status' => 'available'),
        );
    }

    public function get_similar_room_types(array $room)
    {
        $similar = array();
        $cards = $this->table_exists('gh_rooms') ? $this->get_public_master_room_type_cards() : $this->get_public_room_type_cards();
        foreach ($cards as $card) {
            if (isset($room['slug']) && $card['slug'] === $room['slug']) {
                continue;
            }

            if ($card['code'] === $room['code'] || $card['type_name'] === $room['type_name']) {
                $similar[] = $this->normalize_public_room_type_detail($card);
            }

            if (count($similar) >= 3) {
                break;
            }
        }

        if (count($similar) < 3) {
            foreach ($cards as $card) {
                if (isset($room['slug']) && $card['slug'] === $room['slug']) {
                    continue;
                }

                $exists = FALSE;
                foreach ($similar as $item) {
                    if ($item['slug'] === $card['slug']) {
                        $exists = TRUE;
                        break;
                    }
                }

                if ( ! $exists) {
                    $similar[] = $this->normalize_public_room_type_detail($card);
                }

                if (count($similar) >= 3) {
                    break;
                }
            }
        }

        return $similar;
    }

    public function get_room_gallery(array $room)
    {
        return $this->get_uploaded_room_gallery($room);
    }

    public function get_preview_photos($limit = 4)
    {
        $root = realpath(FCPATH . '../uploads');
        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $photos = array();
        $limit = max(1, (int) $limit);

        if (! $root || ! is_dir($root)) {
            return $this->fallback_preview_photos($limit);
        }

        foreach (new DirectoryIterator($root) as $propertyDir) {
            if (! $propertyDir->isDir() || $propertyDir->isDot()) {
                continue;
            }

            $property = strtoupper($propertyDir->getFilename());
            if ($property === 'SLIDER') {
                continue;
            }

            foreach (new DirectoryIterator($propertyDir->getPathname()) as $typeDir) {
                if (! $typeDir->isDir() || $typeDir->isDot()) {
                    continue;
                }

                foreach (new DirectoryIterator($typeDir->getPathname()) as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }

                    $extension = strtolower($file->getExtension());
                    if (! in_array($extension, $allowed, TRUE)) {
                        continue;
                    }

                    $filename = $file->getFilename();
                    $category = $this->gallery_category_from_filename($filename);
                    $photos[] = array(
                        'category' => $category,
                        'title' => $this->gallery_title_from_filename($filename, $category, $propertyDir->getFilename(), $typeDir->getFilename()),
                        'src' => base_url('index.php/media/gallery/' . rawurlencode($propertyDir->getFilename()) . '/' . rawurlencode($typeDir->getFilename()) . '/' . rawurlencode($filename)),
                        'filename' => $filename,
                    );
                }
            }
        }

        usort($photos, function ($left, $right) {
            $leftScore = stripos($left['filename'], 'kamar_01') === 0 ? 0 : (stripos($left['filename'], 'kamar') === 0 ? 1 : 2);
            $rightScore = stripos($right['filename'], 'kamar_01') === 0 ? 0 : (stripos($right['filename'], 'kamar') === 0 ? 1 : 2);

            if ($leftScore === $rightScore) {
                return strnatcasecmp($left['title'], $right['title']);
            }

            return $leftScore - $rightScore;
        });

        return $photos ? $this->select_diverse_preview_photos($photos, $limit) : $this->fallback_preview_photos($limit);
    }

    private function select_diverse_preview_photos(array $photos, $limit)
    {
        $picked = array();
        $usedKeys = array();
        $usedIndexes = array();

        foreach ($photos as $index => $photo) {
            $key = isset($photo['category']) ? strtolower($photo['category']) : 'foto';
            if (isset($usedKeys[$key])) {
                continue;
            }
            $picked[] = $photo;
            $usedKeys[$key] = TRUE;
            $usedIndexes[$index] = TRUE;
            if (count($picked) >= $limit) {
                return $picked;
            }
        }

        foreach ($photos as $index => $photo) {
            if (isset($usedIndexes[$index])) {
                continue;
            }
            $picked[] = $photo;
            if (count($picked) >= $limit) {
                break;
            }
        }

        return array_slice($picked, 0, $limit);
    }

    private function fallback_preview_photos($limit = 4, $roomName = 'GUL HOUSE')
    {
        $photos = array(
            array(
                'category' => 'Kamar',
                'title' => $roomName . ' - Sudut tidur',
                'src' => 'https://images.unsplash.com/photo-1616594039964-ae9021a400a0?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar',
                'title' => $roomName . ' - Area kerja',
                'src' => 'https://images.unsplash.com/photo-1616046229478-9901c5536a45?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar',
                'title' => $roomName . ' - Penyimpanan',
                'src' => 'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Kamar Mandi',
                'title' => 'Kamar mandi bersih',
                'src' => 'https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Fasilitas',
                'title' => 'Koridor dan akses',
                'src' => 'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Fasilitas',
                'title' => 'Ruang bersama',
                'src' => 'https://images.unsplash.com/photo-1600566753376-12c8ab7fb75b?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Area Umum',
                'title' => 'Area santai',
                'src' => 'https://images.unsplash.com/photo-1600210492493-0946911123ea?auto=format&fit=crop&w=1400&q=86',
            ),
            array(
                'category' => 'Area Umum',
                'title' => 'Fasad properti',
                'src' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=86',
            ),
        );

        return array_slice($photos, 0, max(1, (int) $limit));
    }

    private function get_uploaded_room_gallery(array $room)
    {
        $code = isset($room['code']) ? $this->folder_key($room['code']) : '';
        $type = isset($room['type_name']) ? $this->folder_key($room['type_name']) : '';
        $labelCode = isset($room['code']) ? (string) $room['code'] : '';
        $labelType = isset($room['type_name']) ? (string) $room['type_name'] : '';

        if ($code === '' || $type === '') {
            return array();
        }

        $directory = realpath(FCPATH . '../uploads/' . $code . '/' . $type);
        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $gallery = array();

        if (! $directory || ! is_dir($directory)) {
            return array();
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
            $category = $this->gallery_category_from_filename($filename);
            $gallery[] = array(
                'category' => $category,
                'title' => $this->gallery_title_from_filename($filename, $category, $labelCode, $labelType),
                'src' => base_url('index.php/media/gallery/' . rawurlencode($code) . '/' . rawurlencode($type) . '/' . rawurlencode($filename)),
            );
        }

        usort($gallery, function ($left, $right) {
            $order = array('Kamar' => 1, 'Kamar Mandi' => 2, 'Fasilitas' => 3, 'Area Umum' => 4);
            $leftOrder = isset($order[$left['category']]) ? $order[$left['category']] : 99;
            $rightOrder = isset($order[$right['category']]) ? $order[$right['category']] : 99;

            if ($leftOrder === $rightOrder) {
                return strnatcasecmp($left['title'], $right['title']);
            }

            return $leftOrder - $rightOrder;
        });

        return $gallery;
    }

    private function get_uploaded_room_cover($code, $type)
    {
        $code = $this->folder_key($code);
        $type = $this->folder_key($type);

        if ($code === '' || $type === '') {
            return '';
        }

        $directory = realpath(FCPATH . '../uploads/' . $code . '/' . $type);
        $allowed = array('jpg', 'jpeg', 'png', 'webp');
        $files = array();

        if (! $directory || ! is_dir($directory)) {
            return '';
        }

        foreach (new DirectoryIterator($directory) as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower($file->getExtension());
            if (! in_array($extension, $allowed, TRUE)) {
                continue;
            }

            $files[] = $file->getFilename();
        }

        if (! $files) {
            return '';
        }

        usort($files, function ($left, $right) {
            $leftScore = stripos($left, 'kamar_01') === 0 ? 0 : (stripos($left, 'kamar') === 0 ? 1 : 2);
            $rightScore = stripos($right, 'kamar_01') === 0 ? 0 : (stripos($right, 'kamar') === 0 ? 1 : 2);

            if ($leftScore === $rightScore) {
                return strnatcasecmp($left, $right);
            }

            return $leftScore - $rightScore;
        });

        return base_url('index.php/media/gallery/' . rawurlencode($code) . '/' . rawurlencode($type) . '/' . rawurlencode($files[0]));
    }

    private function gallery_category_from_filename($filename)
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

    private function gallery_title_from_filename($filename, $category, $code, $type)
    {
        $base = pathinfo((string) $filename, PATHINFO_FILENAME);
        $base = str_replace(array('_', '-'), ' ', $base);
        $base = trim(preg_replace('/\s+/', ' ', $base));
        $suffix = trim($code . ' ' . $type);

        if ($base === '') {
            return trim($category . ' ' . $suffix);
        }

        return trim(ucwords(strtolower($base)) . ' ' . $suffix);
    }

    public function save_booking_request(array $payload)
    {
        $this->booking_error='';$this->booking_error_field='';
        foreach(array('fullname','phone','room_interest','move_in_plan','message') as $field)$payload[$field]=isset($payload[$field])&&is_scalar($payload[$field])?trim((string)$payload[$field]):'';
        if($payload['fullname']==='')return $this->booking_rejected('fullname','Isi nama lengkap terlebih dahulu.');
        if(mb_strlen($payload['fullname'])>150)return $this->booking_rejected('fullname','Nama lengkap maksimal 150 karakter.');
        if($payload['phone']==='')return $this->booking_rejected('phone','Isi nomor WhatsApp terlebih dahulu.');
        if(!preg_match('/^[0-9]{9,15}$/D',$payload['phone']))return $this->booking_rejected('phone','Nomor WhatsApp hanya boleh berisi angka sebanyak 9–15 digit, tanpa tanda +, spasi, atau tanda baca.');
        if($payload['room_interest']==='')return $this->booking_rejected('room_interest','Pilih minat kamar terlebih dahulu.');
        if($payload['move_in_plan']!=='') {
            if(!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D',$payload['move_in_plan'],$date) || !checkdate((int)$date[2],(int)$date[3],(int)$date[1]))return $this->booking_rejected('move_in_plan','Estimasi mulai tinggal harus berupa tanggal yang valid dengan format YYYY-MM-DD.');
            $today=(new DateTimeImmutable('now',new DateTimeZone('Asia/Jakarta')))->format('Y-m-d');
            if($payload['move_in_plan']<$today)return $this->booking_rejected('move_in_plan','Estimasi mulai tinggal tidak boleh sebelum hari ini (WIB).');
        }
        if(mb_strlen($payload['message'])>2000)return $this->booking_rejected('message','Catatan maksimal 2.000 karakter.');

        try {
        $selected = null;
        foreach ($this->get_all_public_rooms() as $room) {
            if ($room['name'] === $payload['room_interest']) { $selected = $room; break; }
        }
        if(!$selected)return $this->booking_rejected('room_interest','Pilihan kamar tidak ditemukan. Muat ulang halaman lalu pilih tipe kamar yang tersedia.');
        if(empty($selected['available_count']))return $this->booking_rejected('room_interest','Tipe kamar yang dipilih sedang penuh atau tidak tersedia. Pilih tipe lain atau hubungi GUL HOUSE.');

        $this->ensure_booking_table();
        if ( ! $this->table_exists('gh_booking_requests')) {
            return $this->booking_rejected('form','Layanan booking belum tersedia. Silakan coba lagi atau hubungi GUL HOUSE.');
        }

        // Repeated browser submissions create one lead; room assignment happens atomically in master.
        // Archived leads are history: a new request must remain visible in the active master list.
        if ($this->db->field_exists('archived_at', 'gh_booking_requests')) $this->db->where('archived_at', NULL);
        $duplicate = $this->db->where('phone', $payload['phone'])
            ->where('fullname', $payload['fullname'])->where('room_interest', $payload['room_interest'])
            ->where_in('status', array('new', 'contacted', 'survey'))
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 300))
            ->get('gh_booking_requests')->row_array();
        if ($duplicate) return TRUE;
        $saved=$this->db->insert('gh_booking_requests', array(
            'fullname' => $payload['fullname'],
            'phone' => $payload['phone'],
            'room_interest' => $payload['room_interest'],
            'move_in_plan' => $payload['move_in_plan'],
            'message' => $payload['message'],
            'source' => 'landing',
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ));
        if(!$saved)return $this->booking_rejected('form','Permintaan belum dapat disimpan ke database. Coba lagi atau hubungi GUL HOUSE.');
        return TRUE;
        }catch(Throwable $e){
            // Never disclose or log the SQL, payload, phone, or exception contents.
            return $this->booking_rejected('form','Permintaan belum dapat disimpan ke database. Coba lagi atau hubungi GUL HOUSE.');
        }
    }

    private function ensure_booking_table()
    {
        if ($this->table_exists('gh_booking_requests')) {
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `gh_booking_requests` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `fullname` VARCHAR(150) NOT NULL,
            `phone` VARCHAR(40) NOT NULL,
            `room_interest` VARCHAR(150) DEFAULT NULL,
            `move_in_plan` VARCHAR(60) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `source` VARCHAR(40) NOT NULL DEFAULT 'landing',
            `status` ENUM('new','contacted','survey','converted','cancelled','done') NOT NULL DEFAULT 'new',
            `created_at` DATETIME NOT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_status_created` (`status`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    public function find_tenant_payment_access($phone, $nikLast4)
    {
        if ( ! $this->table_exists('gh_tenants') || ! $this->table_exists('gh_tenant_stays') || ! $this->table_exists('gh_rooms')) {
            return null;
        }

        $phoneDigits = $this->only_digits($phone);
        $nikLast4 = $this->only_digits($nikLast4);

        if (strlen($phoneDigits) < 8 || strlen($nikLast4) !== 4) {
            return null;
        }

        $rows = $this->db
            ->select('t.id AS tenant_id, t.fullname, t.identity_number, t.phone')
            ->select('s.id AS stay_id, s.stay_status, s.monthly_price AS stay_price, s.deposit, s.check_in_date')
            ->select('r.id AS room_id, r.room_label, r.monthly_price AS room_price')
            ->select('p.code AS property_code, p.name AS property_name')
            ->select('rt.name AS room_type')
            ->from('gh_tenant_stays s')
            ->join('gh_tenants t', 't.id = s.tenant_id')
            ->join('gh_rooms r', 'r.id = s.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->where_in('s.stay_status', array('active', 'reserved'))
            ->where('RIGHT(t.identity_number, 4) = ' . $this->db->escape($nikLast4), null, FALSE)
            ->order_by('s.stay_status', 'ASC')
            ->order_by('s.id', 'DESC')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            if ($this->phone_matches($row['phone'], $phoneDigits)) {
                return $this->normalize_payment_context($row);
            }
        }

        return null;
    }

    public function get_payment_context($tenantId, $stayId)
    {
        if ( ! $this->table_exists('gh_tenants') || ! $this->table_exists('gh_tenant_stays') || ! $this->table_exists('gh_rooms')) {
            return null;
        }

        $row = $this->db
            ->select('t.id AS tenant_id, t.fullname, t.identity_number, t.phone')
            ->select('s.id AS stay_id, s.stay_status, s.monthly_price AS stay_price, s.deposit, s.check_in_date')
            ->select('r.id AS room_id, r.room_label, r.monthly_price AS room_price')
            ->select('p.code AS property_code, p.name AS property_name')
            ->select('rt.name AS room_type')
            ->from('gh_tenant_stays s')
            ->join('gh_tenants t', 't.id = s.tenant_id')
            ->join('gh_rooms r', 'r.id = s.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->where('t.id', $tenantId)
            ->where('s.id', $stayId)
            ->limit(1)
            ->get()
            ->row_array();

        return $row ? $this->normalize_payment_context($row) : null;
    }

    public function get_current_payment_bill(array $context)
    {
        $period = $this->get_payment_period();

        if ( ! $period || ! $this->table_exists('gh_room_bills')) {
            return $this->draft_payment_bill($context, $period);
        }

        $bill = $this->base_bill_query($context)
            ->where('rb.period_id', (int) $period['id'])
            ->limit(1)
            ->get()
            ->row_array();

        return $bill ? $this->normalize_payment_bill($bill) : $this->draft_payment_bill($context, $period);
    }

    public function get_payment_bill_history(array $context, $limit = 8)
    {
        if ( ! $this->table_exists('gh_room_bills') || ! $this->table_exists('gh_billing_periods')) {
            return array();
        }

        $rows = $this->base_bill_query($context)
            ->order_by('bp.period_key', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();

        $history = array();
        foreach ($rows as $row) {
            $history[] = $this->normalize_payment_bill($row);
        }

        return $history;
    }

    public function get_bill_payments($billId)
    {
        if ( ! $this->table_exists('gh_payments')) {
            return array();
        }

        return $this->db
            ->select('payment_date, payment_date_text, amount, payment_type, method, reference_no, notes')
            ->from('gh_payments')
            ->where('room_bill_id', $billId)
            ->order_by('COALESCE(payment_date, created_at)', 'DESC', FALSE)
            ->order_by('id', 'DESC')
            ->get()
            ->result_array();
    }

    private function base_bill_query(array $context)
    {
        $this->db
            ->select('rb.*, bp.period_key, bp.period_label')
            ->from('gh_room_bills rb')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->where('rb.room_id', (int) $context['room_id']);

        // A shared name or phone must never grant another occupant's invoices.
        $this->db->where('rb.tenant_id', (int) $context['tenant_id']);

        return $this->db;
    }

    public function get_resident_portal(array $context)
    {
        $tenantId = (int) $context['tenant_id'];
        $stayId = (int) $context['stay_id'];
        $contracts = $this->db->where('tenant_id', $tenantId)->where('stay_id', $stayId)
            ->order_by('start_date', 'DESC')->get('gh_contracts')->result_array();
        $documents = $this->db->select('document_type, document_title, status')
            ->where('tenant_id', $tenantId)->get('gh_contract_documents')->result_array();
        $deposits = $this->db->select('transaction_type, amount, transaction_date, description')
            ->where('tenant_id', $tenantId)->where('stay_id', $stayId)
            ->order_by('id', 'DESC')->get('gh_deposit_transactions')->result_array();
        $balance = 0;
        foreach ($deposits as $item) $balance += in_array($item['transaction_type'], array('received','adjustment')) ? (int)$item['amount'] : -(int)$item['amount'];
        $complaints = $this->db->where('tenant_id', $tenantId)->where('room_id', (int)$context['room_id'])
            ->order_by('id', 'DESC')->limit(20)->get('gh_complaints')->result_array();
        return array('contracts'=>$contracts,'documents'=>$documents,'deposit_balance'=>$balance,'complaints'=>$complaints);
    }

    public function submit_resident_complaint(array $context, array $payload)
    {
        $title = trim((string)($payload['complaint_title'] ?? ''));
        $detail = trim((string)($payload['complaint_detail'] ?? ''));
        $category = $payload['category'] ?? 'other';
        if ($title === '' || mb_strlen($title)>180 || mb_strlen($detail)>3000 ||
            !in_array($category,array('facility','noise','billing','security','cleanliness','other'))) return FALSE;
        $duplicate = $this->db->where('tenant_id',(int)$context['tenant_id'])
            ->where('room_id',(int)$context['room_id'])->where('complaint_title',$title)
            ->where('status','open')->where('created_at >=',date('Y-m-d H:i:s',time()-300))
            ->get('gh_complaints')->row_array();
        if ($duplicate) return TRUE;
        return $this->db->insert('gh_complaints',array(
            'ticket_no'=>'GH-C-' . date('Ymd') . '-' . bin2hex(random_bytes(4)),
            'tenant_id'=>(int)$context['tenant_id'],'room_id'=>(int)$context['room_id'],
            'complaint_title'=>$title,'complaint_detail'=>$detail,'category'=>$category,
            'status'=>'open','reported_at'=>date('Y-m-d'),
        ));
    }

    private function get_payment_period()
    {
        if ( ! $this->table_exists('gh_billing_periods')) {
            return null;
        }

        $current = date('Y-m');
        $selected = trim((string) $this->input->get('period', TRUE));
        if (preg_match('/^(\d{4})-(\d{2})$/D', $selected, $parts) && checkdate((int)$parts[2], 1, (int)$parts[1])) {
            $period = $this->db->where('period_key', $selected)->get('gh_billing_periods')->row_array();
            if ($period) return $period;
            return array('id'=>0,'period_key'=>$selected,'period_label'=>strtoupper(date('F Y',strtotime($selected.'-01'))));
        }
        $row = $this->db
            ->where('period_key', $current)
            ->get('gh_billing_periods')
            ->row_array();

        if ($row) {
            return $row;
        }

        $past = $this->db->where('period_key <=', $current)
            ->order_by('period_key', 'DESC')
            ->limit(1)
            ->get('gh_billing_periods')
            ->row_array();
        if ($past) return $past;
        return $this->db->order_by('period_key', 'ASC')->limit(1)->get('gh_billing_periods')->row_array();
    }

    private function draft_payment_bill(array $context, $period = null)
    {
        // An estimate is not an issued invoice, especially for a reserved stay.
        $amount = 0;

        return array(
            'id' => null,
            'period_key' => $period ? $period['period_key'] : date('Y-m'),
            'period_label' => $period ? $period['period_label'] : strtoupper(date('F Y')),
            'base_price' => $amount,
            'deposit_amount' => (int) $context['deposit'],
            'due_date' => null,
            'due_date_text' => '',
            'paid_total' => 0,
            'past_due_amount' => 0,
            'future_due_amount' => 0,
            'remaining_amount' => $amount,
            'bill_status' => 'unknown',
            'notes' => 'Tagihan untuk periode ini belum dibuat di master.',
            'is_draft' => TRUE,
        );
    }

    private function normalize_payment_bill(array $bill)
    {
        $base = (int) $bill['base_price'];
        $deposit = (int) $bill['deposit_amount'];
        $paid = (int) $bill['paid_total'];
        $target = max($base, 0);

        $bill['remaining_amount'] = max(0, $target - $paid);
        $bill['is_draft'] = FALSE;

        return $bill;
    }

    private function normalize_payment_context(array $row)
    {
        $row['tenant_id'] = (int) $row['tenant_id'];
        $row['stay_id'] = (int) $row['stay_id'];
        $row['room_id'] = (int) $row['room_id'];
        $row['stay_price'] = (int) $row['stay_price'];
        $row['room_price'] = (int) $row['room_price'];
        $row['deposit'] = (int) $row['deposit'];
        $row['display_room'] = trim($row['property_code'] . ' - ' . $row['room_label']);
        $row['display_type'] = trim($row['property_code'] . ' - ' . $row['room_type']);

        return $row;
    }

    private function phone_matches($storedPhone, $inputDigits)
    {
        $storedDigits = $this->only_digits($storedPhone);

        if ($storedDigits === '' || $inputDigits === '') {
            return FALSE;
        }

        if ($storedDigits === $inputDigits) {
            return TRUE;
        }

        if (strpos($storedDigits, '62') === 0) $storedDigits = '0' . substr($storedDigits, 2);
        if (strpos($inputDigits, '62') === 0) $inputDigits = '0' . substr($inputDigits, 2);
        return $storedDigits === $inputDigits;
    }

    private function only_digits($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function table_exists($table)
    {
        return $this->db->table_exists($table);
    }

    private function get_room_assets($room_id)
    {
        if ( ! $this->table_exists('room_assets') || ! $this->table_exists('asset_types')) {
            return array();
        }

        $rows = $this->db
            ->select('at.label, ra.description, ra.asset_condition')
            ->from('room_assets ra')
            ->join('asset_types at', 'at.id = ra.asset_type_id', 'left')
            ->where('ra.room_id', $room_id)
            ->order_by('at.label', 'ASC')
            ->get()
            ->result_array();

        $facilities = array();
        foreach ($rows as $row) {
            $facilities[] = $row['label'] ? $row['label'] : $row['description'];
        }

        return array_values(array_filter($facilities));
    }

    private function normalize_room_detail(array $room)
    {
        $room['type_name'] = $room['type_name'] ? $room['type_name'] : 'Room';
        $room['type_description'] = $room['type_description'] ? $room['type_description'] : 'Kamar siap huni dengan fasilitas utama untuk tinggal bulanan.';
        $room['facilities'] = $room['facilities'] ? $room['facilities'] : array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam');
        $room['public_description'] = 'Kamar ' . $room['name'] . ' di ' . $room['code'] . ' cocok untuk penghuni yang ingin tempat tinggal rapi, tenang, dan mudah dijangkau.';
        $room['deposit_estimate'] = (int) $room['price'];
        return $room;
    }

    private function map_gh_rooms(array $rows)
    {
        $rooms = array();
        foreach ($rows as $row) {
            $rooms[] = array(
                'id' => (int) $row['id'],
                'code' => $row['property_code'],
                'name' => $row['room_label'],
                'price' => (int) $row['monthly_price'],
                'status' => ((int) $row['is_mess'] === 1 || ! $row['monthly_price']) ? 'maintenance' : 'available',
                'type_name' => $row['room_type'],
            );
        }

        return $rooms;
    }

    private function get_public_room_type_cards()
    {
        $rows = $this->db
            ->select('property_code', FALSE)
            ->select('room_type AS type_name', FALSE)
            ->select('COUNT(id) AS total_rooms', FALSE)
            ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN 1 ELSE 0 END) AS available_count', FALSE)
            ->select('MIN(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS price', FALSE)
            ->select('MAX(CASE WHEN monthly_price IS NOT NULL AND is_mess = 0 THEN monthly_price END) AS max_price', FALSE)
            ->select('MIN(CASE WHEN deposit IS NOT NULL AND is_mess = 0 THEN deposit END) AS deposit_estimate', FALSE)
            ->from('gh_room_database')
            ->group_by('property_code, room_type')
            ->order_by('property_code', 'ASC')
            ->order_by('FIELD(room_type, "Standart", "Deluxe", "VIP")', '', FALSE)
            ->order_by('room_type', 'ASC')
            ->get()
            ->result_array();

        $cards = array();
        foreach ($rows as $index => $row) {
            $cards[] = array(
                'id' => $index + 1,
                'code' => $row['property_code'],
                'name' => $row['property_code'] . ' - ' . $row['type_name'],
                'slug' => $this->create_public_room_slug($row['property_code'], $row['type_name']),
                'price' => (int) $row['price'],
                'max_price' => (int) $row['max_price'],
                'status' => ((int) $row['available_count'] > 0) ? 'available' : 'maintenance',
                'type_name' => $row['type_name'],
                'total_rooms' => (int) $row['total_rooms'],
                'available_count' => (int) $row['available_count'],
                'deposit_estimate' => (int) $row['deposit_estimate'],
                'cover_image' => $this->get_uploaded_room_cover($row['property_code'], $row['type_name']),
            );
        }

        return $cards ? $cards : $this->fallback_public_room_type_cards();
    }

    private function get_public_master_room_type_cards()
    {
        $citySelect=$this->db->field_exists('city','gh_properties')?'MAX(p.city) AS city':'"" AS city';
        $addressSelect=$this->db->field_exists('address','gh_properties')?'MAX(p.address) AS property_address':'"" AS property_address';
        $rows = $this->db
            ->select('p.code AS property_code', FALSE)
            ->select($citySelect,FALSE)->select($addressSelect,FALSE)
            ->select('rt.name AS type_name', FALSE)
            ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN 1 ELSE 0 END) AS total_rooms', FALSE)
            ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status = "available" AND r.is_public = 1 THEN 1 ELSE 0 END) AS available_count', FALSE)
            ->select('MIN(CASE WHEN r.monthly_price IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN r.monthly_price END) AS price', FALSE)
            ->select('MAX(CASE WHEN r.monthly_price IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN r.monthly_price END) AS max_price', FALSE)
            ->select('MIN(CASE WHEN r.deposit IS NOT NULL AND r.status NOT IN ("inactive", "mess") THEN r.deposit END) AS deposit_estimate', FALSE)
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->group_by('p.id, rt.id')
            ->having('total_rooms >', 0)
            ->order_by('p.code', 'ASC')
            ->order_by('FIELD(rt.name, "Standart", "Deluxe", "VIP")', '', FALSE)
            ->order_by('rt.name', 'ASC')
            ->get()
            ->result_array();

        $cards = array();
        foreach ($rows as $index => $row) {
            $cards[] = array(
                'id' => $index + 1,
                'city' => $this->public_property_city($row['property_code'],$row['city'],$row['property_address']),
                'property_address' => trim((string)$row['property_address']),
                'code' => $row['property_code'],
                'name' => $row['property_code'] . ' - ' . $row['type_name'],
                'slug' => $this->create_public_room_slug($row['property_code'], $row['type_name']),
                'price' => (int) $row['price'],
                'max_price' => (int) $row['max_price'],
                'status' => ((int) $row['available_count'] > 0) ? 'available' : 'full',
                'type_name' => $row['type_name'],
                'total_rooms' => (int) $row['total_rooms'],
                'available_count' => (int) $row['available_count'],
                'deposit_estimate' => (int) $row['deposit_estimate'],
                'cover_image' => $this->get_uploaded_room_cover($row['property_code'], $row['type_name']),
            );
        }

        return $cards ? $cards : $this->fallback_public_room_type_cards();
    }

    private function public_property_city($code,$city,$address)
    {
        if(trim((string)$city)!=='')return trim((string)$city);
        if(preg_match('/\b(?:Kota|Kabupaten)\s+([^,;\r\n]+?)(?=\s+[0-9]{5}\b|[,;\r\n]|$)/iu',(string)$address,$match))return mb_convert_case(trim($match[1]),MB_CASE_TITLE,'UTF-8');
        // Verified existing landing map addresses for GH 1/GH 2; never infer an unknown apartment city.
        $verified=array('GH1'=>'Jakarta Barat','GH2'=>'Jakarta Barat');
        $key=preg_replace('/[^A-Z0-9]/','',strtoupper((string)$code));
        return $verified[$key]??'';
    }

    private function fallback_public_room_type_cards()
    {
        $cards = array();
        $fallback = array(
            array('code' => 'GH 1', 'type_name' => 'Standart', 'total_rooms' => 19, 'available_count' => 19, 'price' => 1500000, 'max_price' => 1500000),
            array('code' => 'GH 1', 'type_name' => 'Deluxe', 'total_rooms' => 3, 'available_count' => 3, 'price' => 1750000, 'max_price' => 1750000),
            array('code' => 'GH 1', 'type_name' => 'VIP', 'total_rooms' => 4, 'available_count' => 4, 'price' => 2000000, 'max_price' => 2000000),
            array('code' => 'GH 2', 'type_name' => 'Standart', 'total_rooms' => 17, 'available_count' => 17, 'price' => 2000000, 'max_price' => 2000000),
            array('code' => 'GH 2', 'type_name' => 'Deluxe', 'total_rooms' => 9, 'available_count' => 9, 'price' => 2500000, 'max_price' => 2500000),
            array('code' => 'GH 2', 'type_name' => 'VIP', 'total_rooms' => 8, 'available_count' => 8, 'price' => 3000000, 'max_price' => 3000000),
        );

        foreach ($fallback as $index => $row) {
            $row['id'] = $index + 1;
            $row['name'] = $row['code'] . ' - ' . $row['type_name'];
            $row['slug'] = $this->create_public_room_slug($row['code'], $row['type_name']);
            $row['status'] = ((int) $row['available_count'] > 0) ? 'available' : 'maintenance';
            $row['deposit_estimate'] = 0;
            $row['cover_image'] = $this->get_uploaded_room_cover($row['code'], $row['type_name']);
            $cards[] = $row;
        }

        return $cards;
    }

    private function map_public_type_rows(array $rows)
    {
        $cards = array();
        foreach ($rows as $index => $row) {
            $available = isset($row['available_count']) ? (int) $row['available_count'] : 0;
            $cards[] = array(
                'id' => $index + 1,
                'code' => isset($row['code']) ? $row['code'] : 'GUL HOUSE',
                'name' => (isset($row['code']) ? $row['code'] . ' - ' : 'Kamar ') . $row['type_name'],
                'slug' => $this->create_public_room_slug(isset($row['code']) ? $row['code'] : 'GUL HOUSE', $row['type_name']),
                'price' => (int) $row['price'],
                'max_price' => (int) $row['max_price'],
                'status' => $available > 0 ? 'available' : 'full',
                'type_name' => $row['type_name'],
                'total_rooms' => (int) $row['total_rooms'],
                'available_count' => $available,
                'deposit_estimate' => 0,
                'cover_image' => $this->get_uploaded_room_cover(isset($row['code']) ? $row['code'] : 'GUL HOUSE', $row['type_name']),
            );
        }

        return $cards;
    }

    private function normalize_public_room_type_detail(array $card)
    {
        $priceLabel = 'Rp ' . number_format((int) $card['price'], 0, ',', '.');
        if ((int) $card['max_price'] > (int) $card['price']) {
            $priceLabel .= ' - Rp ' . number_format((int) $card['max_price'], 0, ',', '.');
        }

        $card['notes'] = 'Ringkasan publik per gedung dan tipe kamar.';
        $card['price_label'] = $priceLabel;
        $availabilityText = (int) $card['available_count'] > 0
            ? (int) $card['available_count'] . ' kamar tersedia dari ' . (int) $card['total_rooms'] . ' kamar'
            : 'stok publik sedang penuh dari total ' . (int) $card['total_rooms'] . ' kamar';

        $card['type_description'] = $card['name'] . ' memiliki ' . $availabilityText . '. Harga tetap ditampilkan sebagai referensi dan dapat berbeda sesuai fasilitas atau kondisi kamar.';
        $card['public_description'] = 'Pilihan ' . $card['type_name'] . ' di ' . $card['code'] . ' untuk calon penghuni yang ingin survey kamar tanpa memilih nomor kamar terlebih dahulu.';
        $card['facilities'] = array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam');

        return $card;
    }

    private function create_public_room_slug($property, $type)
    {
        return $this->normalize_slug($property . '-' . $type);
    }

    private function normalize_slug($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        return trim($value, '-');
    }

    private function folder_key($value)
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $value));
    }

    private function pick_featured_gh_rooms(array $rows, $limit)
    {
        $selected = array();
        $seenTypes = array();

        foreach ($rows as $row) {
            if (isset($seenTypes[$row['room_type']])) {
                continue;
            }

            $selected[] = $row;
            $seenTypes[$row['room_type']] = TRUE;

            if (count($selected) >= $limit) {
                return $this->map_gh_rooms($selected);
            }
        }

        foreach ($rows as $row) {
            if (count($selected) >= $limit) {
                break;
            }

            $key = $row['property_code'] . '|' . $row['room_code'] . '|' . $row['room_label'];
            $exists = FALSE;
            foreach ($selected as $selectedRow) {
                $selectedKey = $selectedRow['property_code'] . '|' . $selectedRow['room_code'] . '|' . $selectedRow['room_label'];
                if ($selectedKey === $key) {
                    $exists = TRUE;
                    break;
                }
            }

            if ( ! $exists) {
                $selected[] = $row;
            }
        }

        return $this->map_gh_rooms($selected);
    }

    private function normalize_gh_room_detail(array $row)
    {
        $room = array(
            'id' => (int) $row['id'],
            'code' => $row['property_code'],
            'name' => $row['room_label'],
            'price' => (int) $row['monthly_price'],
            'status' => ((int) $row['is_mess'] === 1 || ! $row['monthly_price']) ? 'maintenance' : 'available',
            'notes' => $row['floor_no'] ? 'Lantai ' . $row['floor_no'] . ' - kode kamar ' . $row['room_code'] : 'Kode kamar ' . $row['room_code'],
            'type_name' => $row['room_type'],
            'type_description' => 'Kamar ' . $row['room_type'] . ' dari database kamar Gul House. Harga dan deposit mengikuti data operasional terakhir.',
            'facilities' => array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Akses 24 jam'),
            'public_description' => 'Kamar ' . $row['room_label'] . ' di ' . $row['property_code'] . ' cocok untuk penghuni yang ingin tempat tinggal rapi, tenang, dan mudah dijangkau.',
            'deposit_estimate' => $row['deposit'] ? (int) $row['deposit'] : (int) $row['monthly_price'],
        );

        return $room;
    }

    private function fallback_room_detail($id)
    {
        $fallback = array(
            'id' => $id,
            'code' => 'GH 2',
            'name' => 'GH 2 - Standart',
            'price' => 2000000,
            'status' => 'available',
            'notes' => '',
            'type_name' => 'Standart',
            'type_description' => 'Kamar siap huni untuk sewa bulanan dengan fasilitas utama yang terdata.',
            'facilities' => array('Kasur', 'Lemari', 'Meja kerja', 'Kursi', 'Kamar mandi', 'Akses 24 jam'),
        );

        return $this->normalize_room_detail($fallback);
    }

    private function fallback_room_types()
    {
        return array(
            array('name' => 'Standart', 'total_rooms' => 36, 'price_from' => 1500000, 'available_count' => 7),
            array('name' => 'Deluxe', 'total_rooms' => 12, 'price_from' => 1750000, 'available_count' => 2),
            array('name' => 'VIP', 'total_rooms' => 15, 'price_from' => 2000000, 'available_count' => 2),
            array('name' => 'Executive', 'total_rooms' => 1, 'price_from' => 3000000, 'available_count' => 0),
        );
    }
}

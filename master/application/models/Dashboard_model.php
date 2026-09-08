<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard_model extends CI_Model
{
    public function get_summary()
    {
        $summary = array(
            'properties' => 0,
            'rooms' => 0,
            'available' => 0,
            'bookings' => 0,
            'monthly_potential' => 0,
        );

        if ($this->db->table_exists('gh_properties')) {
            $summary['properties'] = (int) $this->db->where('is_active', 1)->count_all_results('gh_properties');
        }

        if ($this->db->table_exists('gh_rooms')) {
            $row = $this->db
                ->select('COUNT(id) AS rooms', FALSE)
                ->select('SUM(CASE WHEN status = "available" AND is_public = 1 THEN 1 ELSE 0 END) AS available', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND status IN ("available","occupied","reserved") THEN monthly_price ELSE 0 END) AS monthly_potential', FALSE)
                ->get('gh_rooms')
                ->row_array();

            $summary['rooms'] = (int) $row['rooms'];
            $summary['available'] = (int) $row['available'];
            $summary['monthly_potential'] = (int) $row['monthly_potential'];
        }

        if ($this->db->table_exists('gh_booking_requests')) {
            $summary['bookings'] = (int) $this->db->where('status', 'new')->count_all_results('gh_booking_requests');
        }

        return $summary;
    }

    public function get_property_rows()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array();
        }

        return $this->db
            ->select('p.code, p.name, p.property_type, COUNT(r.id) AS total_rooms', FALSE)
            ->select('SUM(CASE WHEN r.status = "available" AND r.is_public = 1 THEN 1 ELSE 0 END) AS available_rooms', FALSE)
            ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status IN ("available","occupied","reserved") THEN r.monthly_price ELSE 0 END) AS monthly_potential', FALSE)
            ->from('gh_properties p')
            ->join('gh_rooms r', 'r.property_id = p.id', 'left')
            ->group_by('p.id')
            ->order_by('p.code', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_room_type_rows()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array();
        }

        return $this->db
            ->select('p.code AS property_code, rt.name AS type_name, COUNT(r.id) AS total_rooms', FALSE)
            ->select('SUM(CASE WHEN r.status = "available" AND r.is_public = 1 THEN 1 ELSE 0 END) AS available_rooms', FALSE)
            ->select('MIN(CASE WHEN r.monthly_price IS NOT NULL THEN r.monthly_price END) AS min_price', FALSE)
            ->select('MAX(CASE WHEN r.monthly_price IS NOT NULL THEN r.monthly_price END) AS max_price', FALSE)
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->group_by('p.id, rt.id')
            ->order_by('p.code', 'ASC')
            ->order_by('MIN(r.monthly_price)', 'ASC', FALSE)
            ->get()
            ->result_array();
    }

    public function get_recent_bookings()
    {
        if ( ! $this->db->table_exists('gh_booking_requests')) {
            return array();
        }

        return $this->db
            ->order_by('created_at', 'DESC')
            ->limit(6)
            ->get('gh_booking_requests')
            ->result_array();
    }

    public function get_charts()
    {
        return array(
            'revenue_by_property' => $this->chart_revenue_by_property(),
            'rooms_by_status' => $this->chart_rooms_by_status(),
            'revenue_by_type' => $this->chart_revenue_by_type(),
        );
    }

    private function chart_revenue_by_property()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('p.code AS label', FALSE)
            ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status IN ("available","occupied","reserved") THEN r.monthly_price ELSE 0 END) AS value', FALSE)
            ->from('gh_properties p')
            ->join('gh_rooms r', 'r.property_id = p.id', 'left')
            ->group_by('p.id')
            ->order_by('p.code', 'ASC')
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_rooms_by_status()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('status AS label, COUNT(id) AS value', FALSE)
            ->from('gh_rooms')
            ->group_by('status')
            ->order_by('FIELD(status, "available", "occupied", "reserved", "maintenance", "mess", "inactive")', '', FALSE)
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_revenue_by_type()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('CONCAT(p.code, " - ", rt.name) AS label', FALSE)
            ->select('SUM(CASE WHEN r.monthly_price IS NOT NULL AND r.status IN ("available","occupied","reserved") THEN r.monthly_price ELSE 0 END) AS value', FALSE)
            ->from('gh_rooms r')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->group_by('p.id, rt.id')
            ->order_by('p.code', 'ASC')
            ->order_by('MIN(r.monthly_price)', 'ASC', FALSE)
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function pack_chart_rows(array $rows)
    {
        $labels = array();
        $values = array();

        foreach ($rows as $row) {
            $labels[] = (string) $row['label'];
            $values[] = (int) $row['value'];
        }

        return array('labels' => $labels, 'values' => $values);
    }
}

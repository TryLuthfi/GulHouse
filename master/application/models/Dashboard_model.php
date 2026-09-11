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
            'payment_revenue' => 0,
            'payment_period' => '-',
            'payment_count' => 0,
            'unpaid_bills' => 0,
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

        if ($this->db->table_exists('gh_payments') && $this->db->table_exists('gh_billing_periods')) {
            $period = $this->db
                ->order_by('period_key', 'DESC')
                ->limit(1)
                ->get('gh_billing_periods')
                ->row_array();

            if ($period) {
                $summary['payment_period'] = $period['period_label'];
                $row = $this->db
                    ->select('COUNT(pay.id) AS payment_count, COALESCE(SUM(pay.amount), 0) AS payment_revenue', FALSE)
                    ->from('gh_payments pay')
                    ->join('gh_room_bills rb', 'rb.id = pay.room_bill_id')
                    ->where('rb.period_id', (int) $period['id'])
                    ->get()
                    ->row_array();
                $summary['payment_count'] = $row ? (int) $row['payment_count'] : 0;
                $summary['payment_revenue'] = $row ? (int) $row['payment_revenue'] : 0;
                $summary['unpaid_bills'] = (int) $this->db
                    ->where('period_id', (int) $period['id'])
                    ->where_in('bill_status', array('unpaid', 'partial'))
                    ->count_all_results('gh_room_bills');
            }
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
            'payment_by_period' => $this->chart_payment_by_period(),
            'bill_status_by_latest_period' => $this->chart_bill_status_by_latest_period(),
        );
    }

    public function get_latest_payment_rows()
    {
        if ( ! $this->db->table_exists('gh_payments')) {
            return array();
        }

        return $this->db
            ->select('pay.*, bp.period_label, rb.tenant_name_snapshot, r.room_label, p.code AS property_code')
            ->from('gh_payments pay')
            ->join('gh_room_bills rb', 'rb.id = pay.room_bill_id')
            ->join('gh_billing_periods bp', 'bp.id = rb.period_id')
            ->join('gh_rooms r', 'r.id = rb.room_id')
            ->join('gh_properties p', 'p.id = r.property_id', 'left')
            ->order_by('COALESCE(pay.payment_date, pay.created_at)', 'DESC', FALSE)
            ->limit(8)
            ->get()
            ->result_array();
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

    private function chart_payment_by_period()
    {
        if ( ! $this->db->table_exists('gh_payments')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('bp.period_label AS label, COALESCE(SUM(pay.amount), 0) AS value', FALSE)
            ->from('gh_billing_periods bp')
            ->join('gh_room_bills rb', 'rb.period_id = bp.id', 'left')
            ->join('gh_payments pay', 'pay.room_bill_id = rb.id', 'left')
            ->group_by('bp.id')
            ->order_by('bp.period_key', 'ASC')
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_bill_status_by_latest_period()
    {
        if ( ! $this->db->table_exists('gh_room_bills')) {
            return array('labels' => array(), 'values' => array());
        }

        $period = $this->db
            ->order_by('period_key', 'DESC')
            ->limit(1)
            ->get('gh_billing_periods')
            ->row_array();

        if ( ! $period) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('bill_status AS label, COUNT(id) AS value', FALSE)
            ->from('gh_room_bills')
            ->where('period_id', (int) $period['id'])
            ->group_by('bill_status')
            ->order_by('FIELD(bill_status, "paid", "partial", "unpaid", "overpaid", "empty", "reserved", "internal", "unknown")', '', FALSE)
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

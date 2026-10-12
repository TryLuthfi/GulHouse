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
            'occupied' => 0,
            'reserved' => 0,
            'internal' => 0,
            'maintenance' => 0,
            'occupancy_rate' => 0,
            'active_tenants' => 0,
            'room_types' => 0,
            'payment_total_all' => 0,
            'latest_bill_total' => 0,
            'latest_bill_paid' => 0,
            'outstanding_amount' => 0,
            'collection_rate' => 0,
            'import_issues' => 0,
        );

        if ($this->db->table_exists('gh_properties')) {
            $summary['properties'] = (int) $this->db->where('is_active', 1)->count_all_results('gh_properties');
        }

        if ($this->db->table_exists('gh_rooms')) {
            $row = $this->db
                ->select('COUNT(id) AS rooms', FALSE)
                ->select('SUM(CASE WHEN status = "available" AND is_public = 1 THEN 1 ELSE 0 END) AS available', FALSE)
                ->select('SUM(CASE WHEN status = "occupied" THEN 1 ELSE 0 END) AS occupied', FALSE)
                ->select('SUM(CASE WHEN status = "reserved" THEN 1 ELSE 0 END) AS reserved', FALSE)
                ->select('SUM(CASE WHEN status = "mess" THEN 1 ELSE 0 END) AS internal', FALSE)
                ->select('SUM(CASE WHEN status = "maintenance" THEN 1 ELSE 0 END) AS maintenance', FALSE)
                ->select('SUM(CASE WHEN monthly_price IS NOT NULL AND status IN ("available","occupied","reserved") THEN monthly_price ELSE 0 END) AS monthly_potential', FALSE)
                ->get('gh_rooms')
                ->row_array();

            $summary['rooms'] = (int) $row['rooms'];
            $summary['available'] = (int) $row['available'];
            $summary['occupied'] = (int) $row['occupied'];
            $summary['reserved'] = (int) $row['reserved'];
            $summary['internal'] = (int) $row['internal'];
            $summary['maintenance'] = (int) $row['maintenance'];
            $summary['monthly_potential'] = (int) $row['monthly_potential'];
            $sellableRooms = max(0, $summary['rooms'] - $summary['internal'] - $summary['maintenance']);
            $summary['occupancy_rate'] = $sellableRooms > 0 ? (int) round((($summary['occupied'] + $summary['reserved']) / $sellableRooms) * 100) : 0;
        }

        if ($this->db->table_exists('gh_room_types')) {
            $summary['room_types'] = (int) $this->db->where('is_active', 1)->count_all_results('gh_room_types');
        }

        if ($this->db->table_exists('gh_tenant_stays')) {
            $summary['active_tenants'] = (int) $this->db
                ->where_in('stay_status', array('active', 'reserved', 'internal'))
                ->count_all_results('gh_tenant_stays');
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
                $summary['payment_total_all'] = (int) $this->db
                    ->select('COALESCE(SUM(amount), 0) AS total', FALSE)
                    ->get('gh_payments')
                    ->row()
                    ->total;
                $summary['unpaid_bills'] = (int) $this->db
                    ->where('period_id', (int) $period['id'])
                    ->where_in('bill_status', array('unpaid', 'partial'))
                    ->count_all_results('gh_room_bills');
                $billRow = $this->db
                    ->select('COALESCE(SUM(base_price), 0) AS bill_total', FALSE)
                    ->select('COALESCE(SUM(paid_total), 0) AS paid_total', FALSE)
                    ->from('gh_room_bills')
                    ->where('period_id', (int) $period['id'])
                    ->get()
                    ->row_array();
                $summary['latest_bill_total'] = $billRow ? (int) $billRow['bill_total'] : 0;
                $summary['latest_bill_paid'] = $billRow ? (int) $billRow['paid_total'] : 0;
                $summary['outstanding_amount'] = max(0, $summary['latest_bill_total'] - $summary['latest_bill_paid']);
                $summary['collection_rate'] = $summary['latest_bill_total'] > 0
                    ? (int) round(($summary['latest_bill_paid'] / $summary['latest_bill_total']) * 100)
                    : 0;
            }
        }

        if ($this->db->table_exists('gh_payment_import_issues')) {
            $summary['import_issues'] = (int) $this->db->count_all_results('gh_payment_import_issues');
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
            'occupancy_by_property' => $this->chart_occupancy_by_property(),
            'collection_by_period' => $this->chart_collection_by_period(),
            'room_mix_by_type' => $this->chart_room_mix_by_type(),
            'tenant_status' => $this->chart_tenant_status(),
            'booking_status' => $this->chart_booking_status(),
            'payment_import_issues' => $this->chart_payment_import_issues(),
        );
    }

    public function get_payment_issue_rows()
    {
        if ( ! $this->db->table_exists('gh_payment_import_issues')) {
            return array();
        }

        return $this->db
            ->select('issue_type, COUNT(id) AS total', FALSE)
            ->group_by('issue_type')
            ->order_by('total', 'DESC')
            ->limit(6)
            ->get('gh_payment_import_issues')
            ->result_array();
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

    private function chart_occupancy_by_property()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array('labels' => array(), 'datasets' => array());
        }

        $rows = $this->db
            ->select('p.code AS label', FALSE)
            ->select('SUM(CASE WHEN r.status = "available" THEN 1 ELSE 0 END) AS available', FALSE)
            ->select('SUM(CASE WHEN r.status = "occupied" THEN 1 ELSE 0 END) AS occupied', FALSE)
            ->select('SUM(CASE WHEN r.status = "reserved" THEN 1 ELSE 0 END) AS reserved', FALSE)
            ->select('SUM(CASE WHEN r.status = "mess" THEN 1 ELSE 0 END) AS internal', FALSE)
            ->select('SUM(CASE WHEN r.status = "maintenance" THEN 1 ELSE 0 END) AS maintenance', FALSE)
            ->from('gh_properties p')
            ->join('gh_rooms r', 'r.property_id = p.id', 'left')
            ->group_by('p.id')
            ->order_by('p.code', 'ASC')
            ->get()
            ->result_array();

        return $this->pack_dataset_rows($rows, array(
            'available' => 'Available',
            'occupied' => 'Occupied',
            'reserved' => 'Reserved',
            'internal' => 'Internal',
            'maintenance' => 'Maintenance',
        ));
    }

    private function chart_collection_by_period()
    {
        if ( ! $this->db->table_exists('gh_room_bills')) {
            return array('labels' => array(), 'datasets' => array());
        }

        $rows = $this->db
            ->select('bp.period_label AS label', FALSE)
            ->select('COALESCE(SUM(rb.base_price), 0) AS billed', FALSE)
            ->select('COALESCE(SUM(rb.paid_total), 0) AS paid', FALSE)
            ->select('GREATEST(COALESCE(SUM(rb.base_price), 0) - COALESCE(SUM(rb.paid_total), 0), 0) AS outstanding', FALSE)
            ->from('gh_billing_periods bp')
            ->join('gh_room_bills rb', 'rb.period_id = bp.id', 'left')
            ->group_by('bp.id')
            ->order_by('bp.period_key', 'ASC')
            ->get()
            ->result_array();

        return $this->pack_dataset_rows($rows, array(
            'billed' => 'Tagihan',
            'paid' => 'Terbayar',
            'outstanding' => 'Outstanding',
        ));
    }

    private function chart_room_mix_by_type()
    {
        if ( ! $this->db->table_exists('gh_rooms')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('rt.name AS label, COUNT(r.id) AS value', FALSE)
            ->from('gh_rooms r')
            ->join('gh_room_types rt', 'rt.id = r.room_type_id', 'left')
            ->where('r.status !=', 'inactive')
            ->group_by('rt.id')
            ->order_by('value', 'DESC', FALSE)
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_tenant_status()
    {
        if ( ! $this->db->table_exists('gh_tenant_stays')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('stay_status AS label, COUNT(id) AS value', FALSE)
            ->from('gh_tenant_stays')
            ->group_by('stay_status')
            ->order_by('FIELD(stay_status, "active", "reserved", "internal", "empty", "ended")', '', FALSE)
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_booking_status()
    {
        if ( ! $this->db->table_exists('gh_booking_requests')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('status AS label, COUNT(id) AS value', FALSE)
            ->from('gh_booking_requests')
            ->group_by('status')
            ->order_by('COUNT(id)', 'DESC', FALSE)
            ->get()
            ->result_array();

        return $this->pack_chart_rows($rows);
    }

    private function chart_payment_import_issues()
    {
        if ( ! $this->db->table_exists('gh_payment_import_issues')) {
            return array('labels' => array(), 'values' => array());
        }

        $rows = $this->db
            ->select('issue_type AS label, COUNT(id) AS value', FALSE)
            ->from('gh_payment_import_issues')
            ->group_by('issue_type')
            ->order_by('value', 'DESC', FALSE)
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

    private function pack_dataset_rows(array $rows, array $columns)
    {
        $labels = array();
        $datasets = array();

        foreach ($columns as $key => $label) {
            $datasets[$key] = array(
                'label' => $label,
                'values' => array(),
            );
        }

        foreach ($rows as $row) {
            $labels[] = (string) $row['label'];
            foreach ($columns as $key => $label) {
                $datasets[$key]['values'][] = (int) $row[$key];
            }
        }

        return array(
            'labels' => $labels,
            'datasets' => array_values($datasets),
        );
    }
}

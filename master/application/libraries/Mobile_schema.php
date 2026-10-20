<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/** Native action form metadata. Domain validation stays in existing commands. */
class Mobile_schema
{
    public static function fields($module,$action)
    {
        $sets=array(
            'booking.convert'=>array(),
            'booking.done'=>array(),
            'booking.status'=>array('status:select!'),
            'contracts.activate'=>array('signed_at:date!','inspection_confirmed:checkbox!','deposit_deferred:checkbox'),
            'contracts.renew'=>array('end_date:date!','monthly_price:money!'),
            'contracts.checkout'=>array('checkout_date:date!','inspection_confirmed:checkbox!','deduction_amount:money','deduction_reason:textarea','refund_amount:money','method:text','needs_maintenance:checkbox'),
            'contracts.draft'=>array('stay_id:select!','start_date:date!','end_date:date!','monthly_price:money!','deposit_amount:money'),
            'billing.generate'=>array('period_key:month!'),
            'payments.save-bill'=>array('bill_id:select!','base_price:money!','deposit_amount:money','due_date:date','bill_status:select!','notes:textarea'),
            'payments.save-payment'=>array('payment_id:hidden','request_key:hidden','room_bill_id:select!','payment_date:date!','amount:money!','payment_type:select!','method:text','reference_no:text','notes:textarea'),
            'payments.delete-payment'=>array('payment_id:number!'),
            'payment-proofs.upload'=>array('description:textarea'),
            'photos.upload-slider'=>array(),
            'photos.upload-room'=>array('property_code:select!','room_type:select!','category:select!'),
            'photos.rotate'=>array('scope:select!','property:text','type:text','file:text!','degrees:select!'),
            'photos.delete'=>array('scope:select!','property:text','type:text','file:text!'),
            'lifecycle.enroll'=>array('stay_id:select!'),
            'lifecycle.reserve'=>array('end_date:date'),
            'lifecycle.change_room'=>array('room_id:select!','reason:textarea!','resident_confirmed:checkbox!'),
            'lifecycle.link_stay'=>array('stay_id:select!'),
            'lifecycle.inspection_save'=>array('stage:select!','check_date:date!','period:month!','officer_name:text!','notes:textarea','inspection_id:hidden','revision_of:number'),
            'lifecycle.inspection_extract'=>array('inspection_id:number!'),
            'lifecycle.inspection_finalize'=>array('inspection_id:number!','resident_signed:checkbox!','manager_signed:checkbox!','supervisor_signed:checkbox'),
            'lifecycle.checkin'=>array('contract_signed:checkbox!','inspection_confirmed:checkbox!'),
            'lifecycle.maintenance'=>array('inspection_id:number!','item_id:select!','issue_title:text!'),
            'lifecycle.notice'=>array('planned_exit:date!','reason:textarea!','refund_bank:text!','refund_account:text!'),
            'lifecycle.notice_update'=>array('planned_exit:date!','reason:textarea','refund_bank:text!','refund_account:text!','revision_reason:textarea!'),
            'lifecycle.overdue_checkout_review'=>array('planned_exit:date!','reason:textarea!','review_confirmed:checkbox!','retain_debt:checkbox!','refund_bank:text','refund_account:text'),
            'lifecycle.penalty_payment'=>array('bill_id:select!','amount:money!','payment_date:date!','reference_no:text!','request_key:hidden'),
            'lifecycle.checkout_overdue'=>array('actual_date:date!','keys_returned:checkbox!','retain_debt_confirmed:checkbox!','deduction_amount:money','deduction_reason:textarea','disputed_amount:money','refund_bank:text','refund_account:text'),
            'lifecycle.checkout'=>array('actual_date:date!','keys_returned:checkbox!','deduction_amount:money','deduction_reason:textarea','disputed_amount:money','refund_bank:text!','refund_account:text!'),
            'lifecycle.refund'=>array('amount:money!','transfer_date:date!','reference_no:text!'),
            'lifecycle.resolve_dispute'=>array('released_amount:money!','resolution_note:textarea!'),
        );
        $choices=array('stay_status'=>array('reserved'=>'Reservasi','active'=>'Aktif'),'status'=>array('new'=>'Baru','contacted'=>'Dihubungi','survey'=>'Survey','done'=>'Selesai','cancelled'=>'Batal'),'payment_type'=>array('rent'=>'Sewa','deposit'=>'Deposit','other'=>'Lainnya'),'bill_status'=>array('unpaid'=>'Belum lunas','partial'=>'Sebagian','paid'=>'Lunas','overpaid'=>'Lebih bayar','empty'=>'Kosong','reserved'=>'Reservasi','internal'=>'Internal'),'stage'=>array('check_in'=>'Tanda terima awal','routine'=>'Bulanan','check_out'=>'Pemeriksaan keluar'),'scope'=>array('slider'=>'Slider','room'=>'Kamar'),'category'=>array('kamar'=>'Kamar','mandi'=>'Kamar mandi','fasilitas'=>'Fasilitas'),'degrees'=>array('90'=>'90 degrees','270'=>'270 degrees','180'=>'180 degrees'));
        $out=array();foreach($sets[$module.'.'.$action]??array() as $spec){list($key,$type)=explode(':',$spec);$required=substr($type,-1)==='!';$out[$key]=array('label'=>ucwords(str_replace('_',' ',$key)),'type'=>rtrim($type,'!'),'required'=>$required);if(isset($choices[$key]))$out[$key]['options']=$choices[$key];}
        return $out;
    }
}

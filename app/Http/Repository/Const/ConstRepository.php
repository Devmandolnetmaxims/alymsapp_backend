<?php
namespace App\Http\Repository\Const;

use Spatie\Permission\Models\Role;
use App\Models\CompanyType;
use App\Models\VehicleMake;
use App\Models\VehicleModel;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Module;
use App\Models\User;

class ConstRepository
{
    // Get all Constant data.
    public static function RegularData($request) {
        $roles = Role::Where('name', '!=', 'super admin')->get();
        $companyType = CompanyType::all();
        $make = VehicleMake::all();
        $module = Module::all();
        if($request->input('make')) {
            $model = VehicleModel::where('make',$request->input('make'))->get();
        } else {
            $model = VehicleModel::all();
        }
        if(!empty($request->input('company_type')))
        {
            $service = Service::where('company_type',$request->input('company_type'))->get();
        } else {
            $service = Service::all();
        }
        // Invoice status.
        $invoice = [
            'Paid' => Invoice::STATUS_PAID,
            'Un-Paid' => Invoice::STATUS_UNPAID,
            'Partially-Paid' => Invoice::STATUS_PARTIALPAID,
            'Overdue' => Invoice::STATUS_OVERDUE,
            'Bill' => Invoice::BILL,
            'Invoice' => Invoice::INVOICE,
            'Cash' => Invoice::CASH,
            'Cheque' => Invoice::CHEQUE,
            'Credit Card' => Invoice::CREDIT_CARD,
            'Cash Advance' => Invoice::CASH_ADVANCE,
        ];
        $data = [
            'roles' => $roles,
            'company_type' => $companyType,
            'make' => $make,
            'model' => $model,
            'service' => $service,
            'module' => $module,
            'invoice' => $invoice,
        ];
        return response()->json(['data' => $data, 'status' => 1, 'message' => 'Constant Api.']);
    }

    // Call an third party api.
    public function ThirdPartyApi() {
        // $url = 'https://car-api2.p.rapidapi.com/api/makes?direction=asc&sort=id';
        $url = 'https://car-api2.p.rapidapi.com/api/models?sort=id&direction=asc&year=2020';

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'X-RapidAPI-Key: dd34433d1fmsh639bfd0f54cca60p1be05cjsn8fbbb4e9083d',
            'x-rapidapi-host: car-api2.p.rapidapi.com'
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        // Process the response
        $result = json_decode($response, true);

        // foreach($result['data'] as $data) {
        //     VehicleMake::create([
        //         'make' => $data['name'],
        //     ]);
        // }

        // foreach($result['data'] as $data) {
        //     VehicleModel::create([
        //         'make' => $data['make_id'],
        //         'model' => $data['name'],
        //     ]);
        // }
    }
}

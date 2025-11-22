<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\ProvideService;
use App\Models\Customer;
use Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class serviceController extends Controller
{

    // List service profiles and show create form
    public function addServiceName()
    {
        $data = Service::orderBy('id', 'DESC')->get();
        return view('service.addService', ['listItem' => $data]);
    }

    // Save or update single service profile
    public function saveService(Request $req)
    {
        $req->validate([
            'serviceName' => 'required|string|max:191',
            'rate' => 'required|numeric'
        ]);

        if ($req->profileId) {
            $data = Service::find($req->profileId);
            if (!$data) {
                Alert::error('Failed!', 'Service not found');
                return back();
            }
        } else {
            $data = new Service();
        }

        $data->serviceName = $req->serviceName;
        $data->rate = $req->rate;

        if ($data->save()) {
            Alert::success('Success!', 'Service profile created successfully');
            return redirect(route('addServiceName'));
        } else {
            Alert::error('Failed!', 'Service profile creation failed');
            return back();
        }
    }

    // Bulk delete services
    public function bulkDeleteService(Request $req)
    {
        $req->validate([
            'selected' => 'required|array'
        ]);

        $ids = $req->input('selected', []);

        try {
            DB::transaction(function () use ($ids) {
                Service::whereIn('id', $ids)->delete();
            });

            Alert::success('Success!', 'Selected services deleted successfully');
            return redirect(route('addServiceName'));
        } catch (\Exception $e) {
            Log::error('Service bulk delete failed: ' . $e->getMessage());
            Alert::error('Failed!', 'Bulk delete failed');
            return back();
        }
    }

    /** Bulk delete provided services */
    public function bulkDeleteProvidedServices(Request $req){
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if(empty($ids)){ return back()->with('error','No provided services selected'); }
        try{
            ProvideService::whereIn('id',$ids)->delete();
            Alert::success('Deleted','Selected provided services deleted');
        }catch(\Exception $e){
            \Log::error('bulkDeleteProvidedServices failed: '.$e->getMessage());
            Alert::error('Error','Failed to delete selected provided services');
        }
        return back();
    }

    /** Bulk print provided services */
    public function bulkPrintProvidedServices(Request $req)
    {
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if (empty($ids)) {
            return back()->with('error', 'No provided services selected for printing.');
        }

        try {
            // Use the same leftJoin approach as other list methods to ensure customer names are available
            $services = ProvideService::leftJoin('customers', 'customers.id', '=', 'provide_services.customerName')
                ->select('provide_services.*', 'customers.name as customer_name')
                ->whereIn('provide_services.id', $ids)
                ->orderBy('customer_name')
                ->orderBy('provide_services.created_at')
                ->get();

            if ($services->isEmpty()) {
                return back()->with('error', 'Selected services not found.');
            }

            // Group services by the resolved customer name
            $groupedServices = $services->groupBy('customer_name');

            // Load business/store details for header
            $business = \App\Models\BusinessSetup::first();
            return view('service.serviceProvideBulkPrint', ['groupedServices' => $groupedServices, 'business' => $business]);

        } catch (\Exception $e) {
            \Log::error('bulkPrintProvidedServices failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while generating the print view.');
        }
    }

    /** Bulk print provided services as PDF (server-side) */
    public function bulkPrintProvidedServicesPdf(Request $req)
    {
        $ids = (array)$req->input('ids', $req->input('selected', []));
        if (empty($ids)) {
            return back()->with('error', 'No provided services selected for PDF generation.');
        }

        try {
            $services = ProvideService::leftJoin('customers', 'customers.id', '=', 'provide_services.customerName')
                ->select('provide_services.*', 'customers.name as customer_name')
                ->whereIn('provide_services.id', $ids)
                ->orderBy('customer_name')
                ->orderBy('provide_services.created_at')
                ->get();

            if ($services->isEmpty()) {
                return back()->with('error', 'Selected services not found.');
            }

            $groupedServices = $services->groupBy('customer_name');
            $business = \App\Models\BusinessSetup::first();

            // Render a PDF-optimized Blade. Dompdf has limited CSS support so use a simpler view.
            $pdf = Pdf::loadView('service.serviceProvideBulkPrintPdf', ['groupedServices' => $groupedServices, 'business' => $business])
                ->setPaper('a4', 'portrait');

            return $pdf->stream('provided-services-bulk-print.pdf');
        } catch (\Exception $e) {
            \Log::error('bulkPrintProvidedServicesPdf failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while generating the PDF.');
        }
    }

    // Edit service form
    public function editService($id)
    {
        $data = Service::find($id);
        return view('service.addService', ['profile' => $data]);
    }

    // Delete a single service
    public function delService($id)
    {
        $data = Service::find($id);
        if (!$data) {
            Alert::error('Failed!', 'Service not found');
            return back();
        }

        try {
            $data->delete();
            Alert::success('Success!', 'Service deleted successfully');
            return redirect(route('addServiceName'));
        } catch (\Exception $e) {
            Alert::error('Failed!', 'Service delete failed');
            return back();
        }
    }

    // Show provide service page
    public function provideService()
    {
        $service = Service::orderBy('id', 'DESC')->get();
        $customer = Customer::orderBy('id', 'DESC')->get();
        return view('service.provideService', ['customerList' => $customer, 'serviceList' => $service]);
    }

    // Save provided services (multiple rows at once)
    public function saveProvideService(Request $req)
    {
        $req->validate([
            'customerName' => 'required|integer|exists:customers,id',
            'serviceName' => 'required|array',
            'serviceName.*' => 'required|string',
            'rate' => 'required|array',
            'rate.*' => 'required|numeric',
            'qty' => 'required|array',
            'qty.*' => 'required|integer|min:1',
            'note' => 'nullable|string',
        ]);

        $customerId = $req->customerName;
        $note = $req->note ?? null;

        $names = $req->serviceName;
        $rates = $req->rate;
        $qtys = $req->qty;

        try {
            foreach ($names as $idx => $serviceName) {
                $rate = isset($rates[$idx]) ? (float)$rates[$idx] : 0;
                $qty = isset($qtys[$idx]) ? (int)$qtys[$idx] : 1;
                $amount = $rate * $qty;

                $ps = new ProvideService();
                $ps->customerName = $customerId;
                $ps->serviceName = $serviceName;
                $ps->amount = $amount;
                // store qty and rate for reference
                if (\Schema::hasColumn('provide_services', 'qty')) {
                    $ps->qty = $qty;
                }
                if (\Schema::hasColumn('provide_services', 'rate')) {
                    $ps->rate = $rate;
                }
                $ps->note = $note;
                $ps->save();
            }

            Alert::success('Success!', 'Service(s) saved successfully');
            return redirect(route('provideService'));
        } catch (\Exception $e) {
            Log::error('saveProvideService failed: ' . $e->getMessage());
            Alert::error('Failed!', 'Service profile creation failed');
            return back();
        }
    }

    // Provide service list page
    public function serviceProvideList()
    {
        // Load provided services with customer names
        $provideList = ProvideService::leftJoin('customers','customers.id','=','provide_services.customerName')
            ->select('provide_services.*','customers.name as customer_name')
            ->orderBy('provide_services.id','DESC')
            ->get();
        // Distinct customers and services for filter selects
        $customerList = Customer::orderBy('name','ASC')->get(['id','name']);
        $serviceNames = ProvideService::select('serviceName')->distinct()->orderBy('serviceName','ASC')->pluck('serviceName');
        return view('service.serviceList',[ 
            'provideList' => $provideList,
            'customerList' => $customerList,
            'serviceNames' => $serviceNames,
        ]);
    }

    // View single provided service
    public function provideServiceView($id)
    {
        $row = ProvideService::leftJoin('customers','customers.id','=','provide_services.customerName')
            ->select('provide_services.*','customers.name as customer_name')
            ->where('provide_services.id',$id)
            ->first();
        if(!$row){
            Alert::error('Failed!','Provided service not found');
            return redirect()->route('serviceProvideList');
        }
        return view('service.serviceProvideView',[ 'row' => $row ]);
    }

    // Printable view
    public function provideServicePrint($id)
    {
        $row = ProvideService::leftJoin('customers','customers.id','=','provide_services.customerName')
            ->select('provide_services.*','customers.name as customer_name')
            ->where('provide_services.id',$id)
            ->first();
        if(!$row){
            Alert::error('Failed!','Provided service not found');
            return redirect()->route('serviceProvideList');
        }
        return view('service.serviceProvidePrint',[ 'row' => $row ]);
    }

    // Delete a provided service entry
    public function delProvideService($id)
    {
        $row = ProvideService::find($id);
        if(!$row){
            Alert::error('Failed!','Provided service not found');
            return back();
        }
        try {
            $row->delete();
            Alert::success('Deleted','Provided service deleted successfully');
            return back();
        } catch(\Exception $e){
            \Log::error('delProvideService failed: '.$e->getMessage());
            Alert::error('Error','Deletion failed');
            return back();
        }
    }

    // Admin report: show provide_services rows where rate is null/0 or qty is null/0
    public function provideServicesMissingData(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ProvideService::select('provide_services.*', 'customers.name as customer_name')
            ->leftJoin('customers', 'provide_services.customerName', '=', 'customers.id')
            ->where(function($q){
                $q->whereNull('provide_services.rate')
                  ->orWhere('provide_services.rate', 0)
                  ->orWhereNull('provide_services.qty')
                  ->orWhere('provide_services.qty', 0);
            });

        // date range filter
        if ($request->filled('start_date')) {
            $query->whereDate('provide_services.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('provide_services.created_at', '<=', $request->input('end_date'));
        }
        // customer filter
        if ($request->filled('customer_id')) {
            $query->where('provide_services.customerName', $request->input('customer_id'));
        }

        $rows = $query->orderBy('provide_services.id', 'asc')
            ->paginate(25)
            ->appends($request->query());

        // load customers list for filter select
        $customers = \App\Models\Customer::orderBy('name')->get();

        // If AJAX request, return only the table partial HTML
        if ($request->ajax()) {
            return view('admin.partials.provide_services_table', ['rows' => $rows])->render();
        }

        return view('admin.provide_services_missing', ['rows' => $rows, 'customers' => $customers]);
    }

    // Export CSV for the missing-data report (respects same filters)
    public function exportProvideServicesMissing(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ProvideService::select('provide_services.*')
            ->leftJoin('customers', 'provide_services.customerName', '=', 'customers.id')
            ->where(function($q){
                $q->whereNull('provide_services.rate')
                  ->orWhere('provide_services.rate', 0)
                  ->orWhereNull('provide_services.qty')
                  ->orWhere('provide_services.qty', 0);
            });

        if ($request->filled('start_date')) {
            $query->whereDate('provide_services.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('provide_services.created_at', '<=', $request->input('end_date'));
        }
        if ($request->filled('customer_id')) {
            $query->where('provide_services.customerName', $request->input('customer_id'));
        }

        $rows = $query->orderBy('provide_services.id', 'asc')->get();

        $filename = 'provide_services_missing_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $columns = ['id','customerName','customer_name','serviceName','amount','qty','rate','note','created_at'];

        $callback = function() use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                $customerName = $row->customer_name ?? $row->customerName;

                $data = [
                    $row->id,
                    $row->customerName,
                    $customerName,
                    $row->serviceName,
                    $row->amount,
                    $row->qty,
                    $row->rate,
                    $row->note,
                    $row->created_at,
                ];
                fputcsv($out, $data);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}


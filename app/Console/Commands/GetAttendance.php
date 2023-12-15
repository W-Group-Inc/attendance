<?php

namespace App\Console\Commands;
use App\vms;
use App\Attendance;
use Illuminate\Console\Command;

class GetAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:auto_get_attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Attendance from Hik';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        info("START Get Attendance");
        $attendance = Attendance::orderBy('last_id','desc')->first();

        if($attendance == null)
        {
            $attendances = vms::orderBy('id','asc')->get();
        }
        else
        {
            $attendances = vms::where('id','>',$attendance->last_id)->orderBy('id','asc')->get();
        }
        
        foreach($attendances as $att)
        {
            if($att->time_input != '00:00:00')
            {
                if($att->device_name == "HO IN")
                {
                    $attend = Attendance::where('employee_code',$att->card)->where('time_in',date('Y-m-d H:i:s', strtotime($att->date_time)))->first();
                    if($attend == null)
                    {
                        $attendance = new Attendance;
                        $attendance->employee_code  = $att->card;   
                        $attendance->time_in = date('Y-m-d H:i:s',strtotime($att->date_time));
                        $attendance->device_in = $att->serial_number;
                        $attendance->last_id = $att->id;
                        $attendance->save();
                    }
                }
                else if($att->device_name == "HO Out")
                {
                    $time_in_after = date('Y-m-d H:i:s',strtotime($att->date_time));
                    $time_in_before = date('Y-m-d H:i:s', strtotime ( '-23 hour' , strtotime ( $time_in_after ) )) ;
                    $update = [
                        'time_out' =>  date('Y-m-d H:i:s', strtotime($att->date_time)),
                        'device_out' => $att->serial_number,
                        'last_id' =>$att->id,
                    ];
    
                    $attendance_in = Attendance::where('employee_code',$att->card)
                    ->whereBetween('time_in',[$time_in_before,$time_in_after])->first();
    
                    Attendance::where('employee_code',$att->card)
                    ->whereBetween('time_in',[$time_in_before,$time_in_after])
                    ->update($update);
    
                    if($attendance_in ==  null)
                    {
                        $attendance = new Attendance;
                        $attendance->employee_code  = $att->card;   
                        $attendance->time_out = date('Y-m-d H:i:s', strtotime($att->date_time));
                        $attendance->device_out = $att->serial_number;
                        $attendance->last_id = $att->id;
                        $attendance->save(); 
                    }
    
                }
            }
          
        }
        info("End Get Attendance");
        return "success";
        //
    }
}

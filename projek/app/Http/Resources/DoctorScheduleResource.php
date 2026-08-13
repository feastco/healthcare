<?php

namespace App\Http\Resources;

class DoctorScheduleResource extends BaseApiResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'doctor' => $this->whenLoaded('doctor', fn () => new DoctorResource($this->doctor)),
            'created_at' => $this->created_at,
        ];
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flight Booking Limit
    |--------------------------------------------------------------------------
    |
    | The number of flights a pilot may hold at the same time. Flights that are
    | booked or in the air both count towards the limit. Set to 0 for no limit.
    |
    */

    'max_booked_flights' => env('SKYOS_MAX_BOOKED_FLIGHTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Flight Revenue
    |--------------------------------------------------------------------------
    |
    | Ticket revenue booked when a PIREP is accepted, expressed as a multiple of
    | the payout the pilot is owed. A $100 payout with a multiplier of 1.5 books
    | $150 of revenue, so the flight contributes $50. Set to 0 to record no
    | revenue at all.
    |
    */

    'revenue_multiplier' => env('SKYOS_REVENUE_MULTIPLIER', 1.5),

];

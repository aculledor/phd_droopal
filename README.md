# CIT-REHAB-CARDIACA

## Local environment

Local project url: http://rehabilitacion.ddev.site

## Device API

There is 1 available endpoint on the device

### Update routine session status
> `POST /api/routine`
>> body
>>```json
>>{
>>  "user_id": "string",
>>  "routine_id": "string",
>>  "action": "string" // "start", "stop", "pause", "resume" or "reboot"
>>}
>>```
>> response
>>```json
>>{}
>>```


## Website API

There are 4 available endpoints on the website

### Register new device
> `POST /api/glass/register`
>> body
>>```json
>>{}
>>```
>> response
>>```json
>>{
>>  "id": "string", // glass device identifier (univoque by device)
>>  "secret": "string" // glass device secret (univoque by device)
>>}
>>```

### Authorize device
> `POST /api/glass/authorize`
>> body
>>```json
>>{
>>  "id": "string", // glass device identifier (univoque by device)
>>  "secret": "string" // glass device secret (univoque by device)
>>}
>>```
>> response
>>```json
>>{
>>  "token": "string" // access token to use in Authorization header
>>}
>>```

### Get all sessions related to one device
> `GET /api/glass`
>> query parameters
>>```
>>"id": "string" // glass device identifier (univoque by device, get from register endpoint)
>>```
>> headers
>>```
>>"Authorization": "Bearer string" // access token to use in Authorization header (get from authorize endpoint)
>>```
>> response
>>```json
>>{
>>  "metadata": {
>>    "version": "string",
>>    "timestamp": "ISO 8601 format",
>>    "source": "string"
>>  },
>>  "unity_session_routines": {
>>    "user_id": "string",
>>    "routine_id": "string",
>>    "exercises": [
>>      {
>>        "exercise_id": "string",
>>        "duration": "integer (seconds)",
>>        "time_between_events": "integer (seconds)"
>>      },
>>      {
>>        "exercise_id": "string",
>>        "duration": "integer (seconds)",
>>        "time_between_events": "integer (seconds)"
>>      }
>>      // ...
>>    ]
>>  }
>>}
>>```

### Record new exercise results
> `POST /api/exercise`
>> headers
>>```
>>"Authorization": "Bearer string" // access token to use in Authorization header (get from authorize endpoint)
>>```
>> body
>>```json
>>{
>>  "metadata": {
>>    "routine_id": "string",
>>    "user_id": "string"
>>  },
>>  "exercise_event": {
>>    "exercise_id": "string",
>>    "outcome": "string", // "success", "failure", "timeout"
>>    "timestamp": "ISO 8601 format"
>>  },
>>  "movement_data": {
>>    "left_controller_x": "float",
>>    "left_controller_y": "float",
>>    "left_controller_z": "float",
>>    "right_controller_x": "float",
>>    "right_controller_y": "float",
>>    "right_controller_z": "float",
>>    "head_x": "float",
>>    "head_y": "float",
>>    "head_z": "float"
>>  }
>>}
>>```
>> response
>>```json
>>{
>>  "id": "string"
>>}
>>```

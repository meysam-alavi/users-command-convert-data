<table>
    <thead>
        <tr>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @if(!empty($users))
            @foreach($users as $user)
                <tr>
                    <td>{{$user->first_name}}</td>
                    <td>{{$user->last_name}}</td>
                    <td>{{$user->mobile}}</td>
                    <td>{{$user->national_code}}</td>
                </tr>
            @endforeach
        @endif
    </tbody>
</table>

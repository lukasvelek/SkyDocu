async function loadData(_endpointUrl, _token) {
    const data = {
        data: {
            token: _token,
            limit: 5,
            offset: 0,
            properties: [
                "instanceId",
                "currentOfficerId",
                "currentOfficerType",
                "dateCreated",
                "status",
                "dateModified"
            ]
        }
    };

    $.post(
        _endpointUrl,
        JSON.stringify(data),
        function(data) {
            console.log(data);
        }
    );
}
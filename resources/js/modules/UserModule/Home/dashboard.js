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
                "status",
                "dateCreated",
                "dateModified"
            ]
        }
    };

    await $.post(
        _endpointUrl,
        JSON.stringify(data),
        async function(data) {
            await createTable(data);
        }
    );
}

async function createTable(_data) {
    var table = "<table border=\"0\">";
    
    _data = JSON.parse(_data);

    const dataKeys = Object.keys(_data.data);

    table += "<thead><tr id=\"row-header\">";

    for(const key of ["Officer ID", "Status", "Date created", "Date modified"]) {
        table += "<th>" + key + "</th>";
    }

    table += "</tr></thead><tbody>";

    for(const dataRow of dataKeys) {
        const row = _data.data[dataRow];

        table += "<tr>";

        for(const r of ["currentOfficerId", "status", "dateCreated", "dateModified"]) {
            table += "<td id=\"col-" + r + "\">" + row[r] + "</td>";
        }

        table += "</tr>";
    }

    table += "</tbody></table>";

    $("#processes-waiting-for-me").html(table);
}
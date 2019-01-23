function bookDrive() {
  var name = $('#form-name').val()
  var category_id = $('#catList').val()
  var province = $('#provinceList').val()
  var city = $('#cityList').val()
  var dealers_id = $('#salesList').val()
  var sex = $('input[name="formSex"]:checked').val()
  var month = $('#form-month').val()
  var email = $('#form-email').val()
  var day = $('#form-day').val()
  var mobile = $('#form-mobile').val()
  if (!name || !category_id || !province || !city || !dealers_id || !sex || !month || !day || !email || !mobile) {
    alert('请填充表单内容')
    return
  }
  Apitool.drive.addAppoint({
    name: name,
    category_id: category_id,
    province: province,
    city: city,
    dealers_id: dealers_id,
    sex: sex,
    month: month,
    day: day,
    mobile: mobile,
    email: email
  }).then(function (res) {
    switch (res.code) {
      case '0000':
        alert('预约成功')
        $('#testDriveForm').modal('hide')
        location.reload()
        break;
      case '2002':
        alert('未选择经销商')
        break;
      default:
        alert(res.message)
    }
  })
}

function handleGetProvince() {
  getProvince(function (result) {
    var d = document.createDocumentFragment();
    var list = document.getElementById('provinceList')
    for (var x in result) {
      var element = result[x]
      var e = document.createElement('option')
      e.value = element.id
      e.innerText = element.name
      d.appendChild(e)
    }
    $('#provinceList').html(d)
  })
}

function handleGetCity(event) {
  console.log($(event).val());
  getCity($(event).val() || 1, function (result) {
    var d = document.createDocumentFragment();
    var list = document.getElementById('cityList')
    for (var x in result) {
      var element = result[x]
      var e = document.createElement('option')
      e.value = element.id
      e.innerText = element.name
      d.appendChild(e)
    }

    $('#cityList').html(d)
    handleGetSales()
    $(".selectpicker").selectpicker('refresh')
  })
}

function handleGetSales() {
  var cate = $('#catList').val()
  var province = $('#provinceList').val()
  var city = $('#cityList').val()
  queryDealers({
    cate: cate,
    province: province,
    city: city
  }, function (result) {
    var d = document.createDocumentFragment();
    var list = document.getElementById('salesList')
    if (result instanceof Array) {
      for (var x in result) {
        var element = result[x]
        var e = document.createElement('option')
        e.value = element.id
        e.innerText = element.name
        d.appendChild(e)
      };

    } else {
      var e = document.createElement('option')
      e.value = null
      e.innerText = '无'
      d.appendChild(e)
    }

    $('#salesList').html(d)

    $(".selectpicker").selectpicker('refresh')
  })
}

$(function () {
  handleGetProvince()
  handleGetCity()
  handleGetSales()
})



var hasGetCity = false

function getDrivelistInline() {
  getDrivelist(function (data) {
    appendOptions('#loc-typelist', data, true)
  })
}

function getProvinceInline(e) {
  console.log(e.value)
  getProvince(function (data) {
    appendOptions('#loc-province', data)
    if (!hasGetCity) {
      getCityInline(data[0].id, null)
      hasGetCity = true
    }
  })
}

function appendOptions(id, data, isAppend) {
  var html = ''
  for (var x in data) {
    var element = data[x]
    html += '<option value=' + element.id + '>' + element.name + '</option>'
  }
  if (isAppend) {
    $(id).append(html)
  } else {
    $(id).html(html)
  }
  $(".selectpicker").selectpicker('refresh')
}

function getCityInline(id, e) {
  if (id) {
    getCity(id, function (data) {
      appendOptions('#loc-city', data)
    })
  } else {
    getCity(e.value, function (data) {
      appendOptions('#loc-city', data)
    })
  }
}

function getSalesInline() {
  var cate_id2 = $('#loc-typelist').val()
  var province = $('#loc-province').val()
  var city = $('#loc-city').val()

  querySales({
    cate: cate_id2,
    province: province,
    city: city
  }, function (data) {
    if (data instanceof Array && data.length > 0) {
      var html = ''
      for (var x in data) {
        var element = data[x]
        html += '<li><h5>' + element.name + '</h5><p>' + element.address + '</p><p>' + element.mobile + '</p></li>'
      }
      $('#dealerList').html(html)
    } else {
      $('#dealerList').html('<p class="text-center">暂无经销商</p>')
    }
  })
}

$(function () {
  getDrivelistInline()
})
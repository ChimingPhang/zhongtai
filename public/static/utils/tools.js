function getDrivelist(cb) {
  Apitool.drive.getDrivelist().then(function (res) {
    cb(res.data)
  })
}


function getProvince(cb) {
  Apitool.drive.getProvince().then(function (res) {
    cb(res.data)
  })
}

function getCity(id, cb) {
  Apitool.drive.getCity(id).then(function (res) {
    console.log(res)
    cb(res.data)
  })
}

function queryDealers(params, cb) {
  Apitool.drive.queryDealers(params.cate, params.province, params.city).then(function (res) {
    console.log(res)
    cb(res.data)
  })
}

function querySales(params, cb) {
  Apitool.drive.querySales(params.cate, params.province, params.city).then(function (res) {
    console.log(res)
    cb(res.data)
  })
}
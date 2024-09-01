const token = '';
async function axiosGet(url, data) {
  let result = { 'code': 0 };
  await axios.get(url, { headers: { 'X-CSRF-TOKEN': this.token }, params: data })
    .then(function (response) {
      if (response.status == 200) {
        if (response.data.code == 1) {
          result = response.data;
        } else {
          console.log('报错: ' + response.data.msg);
        }
      }
    })
    .catch((res) => {
      console.log('报错: ' + res);
    });
  return result;
}

async function axiosPost(url, data) {
  let result = {};
  await axios.post(url, data, { headers: { 'X-CSRF-TOKEN': this.token } })
    .then(function (response) {
      if (response.status == 200) {
        if (response.data.code == 1) {
          result = { 'state': 'success', 'msg': response.data.msg };
        } else {
          result = { 'state': 'warning', 'msg': response.data.msg };
        }
      } else {
        result = { 'state': 'error', 'msg': '系统出错' };
        console.log('系统出错: ' + res);
      }
    }).catch(function (res) {
      result = { 'state': 'error', 'msg': '未知错误' };
      console.log('未知错误: ' + res);
    });
  return result;
}

async function axiosDownload(url, data) {
  axios.post(url, data, { headers: { 'X-CSRF-TOKEN': this.token }, responseType: 'blob' })
    .then((res) => {
      const { data, headers } = res
      const contentDisposition = headers['content-disposition']
      const patt = new RegExp('filename=([^;]+\.[^\.;]+);*')
      const result = patt.exec(contentDisposition)
      const filename = decodeURI(JSON.parse(result[1])) // 处理文件名,解决中文乱码问题
      const blob = new Blob([data], { type: headers['content-type'] })
      let dom = document.createElement('a')
      let url = window.URL.createObjectURL(blob)
      dom.href = url
      dom.download = decodeURI(filename)
      dom.style.display = 'none'
      document.body.appendChild(dom)
      dom.click()
      dom.parentNode.removeChild(dom)
      window.URL.revokeObjectURL(url)
    }).catch((err) => { })
}

function getCurrentDateString() {
  let date = new Date();
  let year = date.getFullYear();
  let month = (date.getMonth() + 1).toString().padStart(2, '0');
  let day = date.getDate().toString().padStart(2, '0');
  return `${year}-${month}-${day}`;
}
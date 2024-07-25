axios.interceptors.request.use(
    function (config) {
        if (getCookie('X-Token')) {
            // 判断是否存在 token, 如果存在的话, 则每个 http header 都加上 token
            config.headers['X-Token'] = getCookie('X-Token');
        }
        // 在发送请求之前进行操作
        return config;
    },
    function (error) {

        // 对请求错误进行操作
        return Promise.reject(error);
    }
);

// 相应拦截器
axios.interceptors.response.use(
    function (response) {
        // console.log(response);
        if(response['data']['code'] && response['data']['code'] == 401){
            window.location.href = 'https://pingansuiyue.crzfxjzn.com/node/login.php';
            return false;
        }
        // 对响应数据进行操作
        return response;
    },
    function (error) {
        // 对响应错误进行操作
        return Promise.reject(error);
    }
);

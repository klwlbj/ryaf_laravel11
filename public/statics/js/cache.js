function setCache(key, value, expires) {
    const now = new Date().getTime();
    const cache = {
        value: value,
        expires: now + expires // 过期时间（毫秒）
    };
    localStorage.setItem(key, JSON.stringify(cache));
}

// 获取缓存
function getCache(key) {
    const cache = JSON.parse(localStorage.getItem(key));
    if (!cache) return null;

    const now = new Date().getTime();
    if (now > cache.expires) {
        // 已过期，删除缓存
        localStorage.removeItem(key);
        return null;
    }
    return cache.value;
}

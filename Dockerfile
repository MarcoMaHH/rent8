# 二开推荐阅读[如何提高项目构建效率](https://developers.weixin.qq.com/miniprogram/dev/wxcloudrun/src/scene/build/speed.html)
# 选择构建用基础镜像（选择原则：在包含所有用到的依赖前提下尽可能体积小）。如需更换，请到[dockerhub官方仓库](https://hub.docker.com/_/php?tab=tags)自行选择后替换。
FROM alpine:3.20

# 容器默认时区为UTC，如需使用上海时间请启用以下时区设置命令
RUN apk add tzdata && cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && echo Asia/Shanghai > /etc/timezone

# 使用 HTTPS 协议访问容器云调用证书安装
RUN apk add ca-certificates

# 安装依赖包，如需其他依赖包，请到alpine依赖包管理(https://pkgs.alpinelinux.org/packages?name=php8*imagick*&branch=v3.13)查找。
# 选用国内镜像源以提高下载速度
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.tencent.com/g' /etc/apk/repositories \
    && apk add --update --no-cache \
    php82 \
    php82-json \
    php82-ctype \
	php82-exif \
	php82-pdo \
	php82-zip \
    php82-pdo_mysql \
    php82-fpm \
    php82-xml \
    php82-xmlwriter \
    php82-curl \
    php82-session \
    apache2 \
    php82-apache2 \
    cronie \
    curl \
    && rm -f /var/cache/apk/*

# 设定工作目录
WORKDIR /app

# 将当前目录下所有文件拷贝到/app（.dockerignore中文件除外）
COPY . /app

# 复制入口脚本到镜像中
COPY entrypoint.sh /entrypoint.sh

# 修改文件目录权限
# 替换apache配置文件
RUN chown -R apache:apache /app \
    && chmod -R 755 /app \
    && chmod -R 777 /app/runtime \
    && cp /app/conf/httpd.conf /etc/apache2/httpd.conf \
    && cp /app/conf/php.ini /etc/php82/php.ini \
    && mv /usr/sbin/php-fpm82 /usr/sbin/php-fpm

# 设置入口脚本权限
RUN chmod +x /entrypoint.sh

# 暴露端口
# 此处端口必须与「服务设置」-「流水线」以及「手动上传代码包」部署时填写的端口一致，否则会部署失败。
EXPOSE 80

# 设置容器启动时执行的命令为入口脚本
ENTRYPOINT ["/entrypoint.sh"]

# 由于入口脚本将负责启动 cron 和 Apache，因此不需要 CMD 指令
# CMD ["httpd", "-DFOREGROUND"]

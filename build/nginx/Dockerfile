FROM nginx:latest

ARG username
ENV USERNAME=$username

#set folder owner
RUN mkdir -p /var/www/html
RUN useradd -m $USERNAME
RUN chown -R $USERNAME:$USERNAME /var/www/html

# Make sure the volume mount point is empty
RUN rm -rf /var/www/html/*

COPY ./build/nginx/conf/nginx.conf /etc/nginx/

RUN echo "set \$username $USERNAME;\n" >> /etc/nginx/var.conf
RUN echo "set \$domain php;\n" >> /etc/nginx/var.conf

RUN echo "ssl_certificate /etc/certs/magento.local.pem;\n" >> /etc/nginx/ssl.conf
RUN echo "ssl_certificate_key /etc/certs/magento.local.key;\n" >> /etc/nginx/ssl.conf

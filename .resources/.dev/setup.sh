#!/usr/bin/env bash

# Install function for web root


#DNS Setup

echo "Setting up Google Domains DNS Configuration.."

mkdir -p /etc/dns/
cat <<EOF > /etc/dns/update.sh
    # DNS Update code
    wget https://$DNS_USERNAME:$DNS_PASSWORD@domains.google.com/nic/update?hostname=$DNS_WEBSITE -qO- >> output.txt
    echo " - was set at: `date`" >> output.txt
EOF

echo "DNS Setup complete!"



#Web Dir setup

cp -rf ../../ /var/www/


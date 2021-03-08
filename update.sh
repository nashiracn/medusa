#!/bin/bash
#composer config repositories.packagist.org false
#composer config repositories.zxedu composer https://composer.zhixue-edu.com:60443/
U=$(whoami)
if [ "$U" != "www-data" ]
then
	if [ "$U" != "root" ]
	then
		echo "Require User \`www-data\`"
		exit 2;
	fi
fi


update_satis()
{
    #export ALL_PROXY=socks5h://192.168.1.10:1988
    git config --global --add http.proxy socks5://192.168.1.10:1988
	/mnt/nvme0n1/ci/medusa/bin/medusa $1 /mnt/nvme0n1/ci/medusa/medusa.json
    if [ $? -ne 0 ] ; then
        return 1
    fi

    #export ALL_PROXY=
    git config --global --unset-all http.proxy
    /mnt/nvme0n1/ci/medusa/vendor/bin/satis build --skip-errors /mnt/nvme0n1/ci/medusa/satis.json /mnt/nvme0n1/ci/web
    if [ $? -ne 0 ] ; then
        return 2
    fi

	return 0
}

if [ "$1" = "update" ]
then
    FUNC=update
else
    FUNC=mirror
fi

if [ "$U" = "root" ]
then
	sudo -uwww-data bash -c "$(declare -f update_satis); update_satis $FUNC"
	RET=$?
else
	update_satis $FUNC
	RET=$?
fi

exit $RET

%define __xarroot /var/www/xaraya

Summary:	Web Application frame work written in PHP.
Name:		Xaraya
Version:	0.9
Release:	1
Source0:	Xaraya-%{version}.tar.bz2
License:	GPL
Url:		http://www.xaraya.com
Icon:		xaraya.gif
Group:		System/Servers
Requires:	webserver php-common mod_php
BuildRoot:	%_tmppath/%name-%version-buildroot
BuildArch:	noarch
Prefix:		%__xarroot

%description
Web application framework writen in php. 
Can be used as a Content Management System, weblog, or anything else that comes to mind.
Very powerful, yet easy to install and use: see documentation in 
%{_docdir}/%{name}-%{version} for details. 

You only have to :
	Change the DocumentRoot in Apache config to point to the Xaraya html directory (%{__xarroot}/xaraya/html
	and then run http://host.name/install.php
	follow the instructions to complete the simple installation process.
	
[read %{_docdir}/%{name}-%{version}/readme.txt
 for further information .-)]

%prep

%setup -q -c %{name}-%{version} -a0

# workaround for bad tarball
find -empty -type f | xargs rm -f
find -type f -exec chmod 644 '{}' \; -o -type d -exec chmod 755 '{}' \;

%build

%install
rm -rf %{buildroot}
mkdir -p %{buildroot}%__xarroot
cp -ar xaraya/* %{buildroot}%__xarroot/

%clean
rm -rf %{buildroot}

%files
%defattr(-, root, root)
%config(noreplace) %attr(666) %__xarroot/html/var/config.system.php
%config(noreplace) %attr(666) %__xarroot/html/var/config.site.xml
%__xarroot

%changelog
* Mon Feb 10 2003 Chris Dudley <miko@xaraya.com> 0.9-1
- First RPM of Xaraya


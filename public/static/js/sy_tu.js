
<!--��ҳ��̬��Ʒ-->
<!--
var FitWidth = 150;
var FitHeight = 150;
function ResizePic(ImgTag)
{
    var image = new Image();
 image.src = ImgTag.src;
 if(image.width>0 && image.height>0){
  if(image.width/image.height >= FitWidth/FitHeight){
   if(image.width > FitWidth){
    ImgTag.width = FitWidth;
    ImgTag.height = (image.height*FitWidth)/image.width;
   }
   else{ 
    ImgTag.width = image.width;
    ImgTag.height = image.height;
   }
  }
  else{
   if(image.height > FitHeight){
    ImgTag.height = FitHeight;
    ImgTag.width = (image.width*FitHeight)/image.height;
   }
   else{
    ImgTag.width = image.width; 
    ImgTag.height = image.height;
   }
  }
 }
}
//-->
<!--��ҳ������Ʒ-->
<!--
var FitWidth1 = 150;
var FitHeight1 = 150;
function ResizePic1(ImgTag)
{
    var image = new Image();
 image.src = ImgTag.src;
 if(image.width>0 && image.height>0){
  if(image.width/image.height >= FitWidth1/FitHeight1){
   if(image.width > FitWidth1){
    ImgTag.width = FitWidth1;
    ImgTag.height = (image.height*FitWidth1)/image.width;
   }
   else{ 
    ImgTag.width = image.width;
    ImgTag.height = image.height;
   }
  }
  else{
   if(image.height > FitHeight1){
    ImgTag.height = FitHeight1;
    ImgTag.width = (image.width*FitHeight1)/image.height;
   }
   else{
    ImgTag.width = image.width; 
    ImgTag.height = image.height;
   }
  }
 }
}
//-->
<!--��ҳ��Ʒ��ͼ-->
<!--
var FitWidth_nycp = 280;
var FitHeight_nycp = 280;
function ResizePic_nycp(ImgTag)
{
    var image = new Image();
 image.src = ImgTag.src;
 if(image.width>0 && image.height>0){
  if(image.width/image.height >= FitWidth_nycp/FitHeight_nycp){
   if(image.width > FitWidth_nycp){
    ImgTag.width = FitWidth_nycp;
    ImgTag.height = (image.height*FitWidth_nycp)/image.width;
   }
   else{ 
    ImgTag.width = image.width;
    ImgTag.height = image.height;
   }
  }
  else{
   if(image.height > FitHeight_nycp){
    ImgTag.height = FitHeight_nycp;
    ImgTag.width = (image.width*FitHeight_nycp)/image.height;
   }
   else{
    ImgTag.width = image.width; 
    ImgTag.height = image.height;
   }
  }
 }
}
//-->